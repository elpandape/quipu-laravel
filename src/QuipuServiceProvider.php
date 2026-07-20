<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel;

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\CertificatePreflight;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolverFactory;
use ElPandaPe\QuipuLaravel\Certificate\NullCertificatePreflight;
use ElPandaPe\QuipuLaravel\Certificate\ProCertificatePreflight;
use ElPandaPe\QuipuLaravel\Console\CdrFetchCommand;
use ElPandaPe\QuipuLaravel\Console\DoctorCommand;
use ElPandaPe\QuipuLaravel\Console\InstallCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\CertConvertCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\CertificateExpiryCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\CertInspectCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\DiagnoseCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\SmartRetryCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\XmlDiffCommand;
use ElPandaPe\QuipuLaravel\Console\Pro\XmlInspectCommand;
use ElPandaPe\QuipuLaravel\Console\PruneCommand;
use ElPandaPe\QuipuLaravel\Console\ReadCommand;
use ElPandaPe\QuipuLaravel\Console\SendCommand;
use ElPandaPe\QuipuLaravel\Console\StatusCommand;
use ElPandaPe\QuipuLaravel\Console\SummaryCommand;
use ElPandaPe\QuipuLaravel\Diagnosing\NullRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\ProRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterFactory;
use ElPandaPe\QuipuLaravel\Emitter\ProEmitterFactory;
use ElPandaPe\QuipuLaravel\Idempotency\DatabaseResultStore;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Scheduling\ScheduleRegistrar;
use ElPandaPe\QuipuLaravel\Storage\DocumentStorage;
use ElPandaPe\QuipuLaravel\Storage\StoragePaths;
use ElPandaPe\QuipuLaravel\Support\CarbonClock;
use ElPandaPe\QuipuLaravel\Tenancy\TenancyEmitterResolverFactory;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContext;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContextFactory;
use ElPandaPe\QuipuPro\Idempotency\ResultStore;
use ElPandaPe\QuipuPro\Logging\OperationLogger;
use ElPandaPe\QuipuPro\QuipuPro;
use ElPandaPe\QuipuPro\Retry\RetryPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Wires quipu into the Laravel container: merges the package config, binds the
 * replaceable EmitterConfigResolver seam, resolves a ready-to-use Lite Quipu
 * emitter, and registers the persistence pieces (disk-backed DocumentStorage,
 * the correlativo counter's connection resolver). Migrations are auto-loaded
 * and also publishable. Registered via package auto-discovery.
 */
final class QuipuServiceProvider extends ServiceProvider
{
    private const string CONFIG_PATH = __DIR__ . '/../config/quipu.php';

    private const string MIGRATIONS_PATH = __DIR__ . '/../database/migrations';

    public function register(): void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'quipu');

        // The active-emitter seam, selected from config('quipu.tenancy.driver')
        // by the TenancyEmitterResolverFactory: the single-emitter config
        // resolver for "none" (the default, mono-tenant), or a per-tenant
        // resolver for "stancl"/"spatie"/"auto"/<class>. Package availability is
        // resolved here (the only place that touches the optional tenancy
        // classes) and injected, so a Lite/Pro-only install never loads them.
        // Resolving stays lazy, so the app still boots either way.
        $this->app->bind(TenancyEmitterResolverFactory::class, static fn(Application $app): TenancyEmitterResolverFactory => new TenancyEmitterResolverFactory(
            $app,
            $app->make(Repository::class),
            stanclAvailable: class_exists(\Stancl\Tenancy\Tenancy::class),
            spatieAvailable: interface_exists(\Spatie\Multitenancy\Contracts\IsTenant::class),
        ));

        $this->app->bind(EmitterConfigResolver::class, static fn(Application $app): EmitterConfigResolver => $app->make(TenancyEmitterResolverFactory::class)->make());

        // The active-tenant runtime, selected from the same driver setting: the
        // key that scopes persistence and correlativos, the per-tenant storage
        // disk, and Quipu::forTenant. Package availability is injected (the only
        // place that touches the optional tenancy classes), mirroring the emitter
        // resolver seam above; resolution stays lazy so the app boots either way.
        $this->app->bind(TenantContextFactory::class, static fn(Application $app): TenantContextFactory => new TenantContextFactory(
            $app,
            $app->make(Repository::class),
            stanclAvailable: class_exists(\Stancl\Tenancy\Tenancy::class),
            spatieAvailable: interface_exists(\Spatie\Multitenancy\Contracts\IsTenant::class),
        ));

        $this->app->bind(TenantContext::class, static fn(Application $app): TenantContext => $app->make(TenantContextFactory::class)->make());

        $this->app->bind(CertificateResolver::class, static fn(Application $app): CertificateResolver => new CertificateResolverFactory(
            $app->make(Repository::class),
            $app->make(FilesystemFactory::class),
        )->make());

        $this->registerProDetection();

        // The emitter: Pro-composed (resilient sender + Pro validators) when the
        // Pro edition is active, the plain Lite emitter otherwise. The Pro path
        // shares the single QuipuPro instance so the emitter and the fluent
        // builders resolve the same composition. The selection is made at resolve
        // time so config('quipu.pro') can be set late.
        $this->app->singleton(Quipu::class, static function (Application $app): Quipu {
            if ($app->make(ProDetector::class)->isActive()) {
                return $app->make(QuipuPro::class)->core();
            }

            return $app->make(EmitterFactory::class)->make($app->make(EmitterConfigResolver::class)->resolve());
        });

        $this->app->bind(ConnectionResolverInterface::class, static function (Application $app): ConnectionResolverInterface {
            $resolver = $app->make('db');
            assert($resolver instanceof ConnectionResolverInterface);

            return $resolver;
        });

        $this->app->bind(DocumentStorage::class, static function (Application $app): DocumentStorage {
            $config = $app->make(Repository::class);

            // When the active tenant defines its own disk, sign/CDR land there;
            // otherwise (mono-tenant, or a tenant without an override) the global
            // config('quipu.storage.disk') is used. Resolved per use, so each
            // dispatch under a tenant picks up that tenant's disk.
            $disk = $app->make(TenantContext::class)->currentTenantStorageDisk()
                ?? self::configString($config, 'quipu.storage.disk', 'local');

            return new DocumentStorage(
                $app->make(FilesystemFactory::class)->disk($disk),
                new StoragePaths(
                    signed: self::configString($config, 'quipu.storage.paths.signed', 'signed'),
                    cdr: self::configString($config, 'quipu.storage.paths.cdr', 'cdr'),
                    inbox: self::configString($config, 'quipu.storage.paths.inbox', 'inbox'),
                ),
            );
        });

        $this->app->bind(QuipuLogger::class, static fn(Application $app): QuipuLogger => new QuipuLogger(self::logChannel($app)));
    }

    /**
     * Auto-detection of the Pro edition and the bindings its composition needs.
     * Every binding is lazy: the Pro classes are only instantiated when the Pro
     * edition is active, so a Lite-only install never touches them.
     */
    private function registerProDetection(): void
    {
        $this->app->bind(ProDetector::class, static fn(Application $app): ProDetector => ProDetector::fromConfig($app->make(Repository::class)->get('quipu.pro')));

        // The Pro capstone, shared between the emitter and the fluent builders.
        // Lazy: only resolved on a Pro-active install, so a Lite-only app never
        // touches the QuipuPro classes.
        $this->app->singleton(QuipuPro::class, static function (Application $app): QuipuPro {
            $config = $app->make(EmitterConfigResolver::class)->resolve();

            return $app->make(ProEmitterFactory::class)->makePro($config);
        });

        // Persistent idempotency store, injected into the Pro composition in
        // place of the in-memory default.
        $this->app->bind(ResultStore::class, DatabaseResultStore::class);

        // Certificate pre-flight for the enriched doctor: the Pro adapter when
        // active, a null-object otherwise (basic doctor unchanged).
        $this->app->bind(CertificatePreflight::class, static fn(Application $app): CertificatePreflight => $app->make(ProDetector::class)->isActive()
            ? new ProCertificatePreflight(new CarbonClock())
            : new NullCertificatePreflight());

        // Pro operation logger over quipu's own log channel.
        $this->app->bind(OperationLogger::class, static fn(Application $app): OperationLogger => new OperationLogger(self::logChannel($app)));

        // Retry policy from config for the resilient sender.
        $this->app->bind(RetryPolicy::class, static function (Application $app): RetryPolicy {
            $config = $app->make(Repository::class);

            return new RetryPolicy(
                maxAttempts: self::configInt($config, 'quipu.retry.max_attempts', 3),
                baseDelayMs: self::configInt($config, 'quipu.retry.base_delay_ms', 1000),
                factor: self::configFloat($config, 'quipu.retry.factor', 2.0),
                capDelayMs: self::configInt($config, 'quipu.retry.cap_delay_ms', 30000),
            );
        });

        // Rejection diagnoser: the Pro adapter when active, a null-object
        // otherwise (base behaviour unchanged).
        $this->app->bind(RejectionDiagnoser::class, static fn(Application $app): RejectionDiagnoser => $app->make(ProDetector::class)->isActive()
            ? new ProRejectionDiagnoser()
            : new NullRejectionDiagnoser());
    }

    public function boot(): void
    {
        // Zero-config: migrations run on `php artisan migrate` (and in tests).
        $this->loadMigrationsFrom(self::MIGRATIONS_PATH);

        $this->publishes([
            self::CONFIG_PATH => config_path('quipu.php'),
        ], 'quipu-config');

        // Also ejectable for teams that want to own/customize the schema.
        $this->publishesMigrations([
            self::MIGRATIONS_PATH => database_path('migrations'),
        ], 'quipu-migrations');

        $this->registerCommands();
        $this->registerSchedule();
    }

    private function registerCommands(): void
    {
        // Safe to call unconditionally: commands() defers to the console kernel,
        // which only loads them when Artisan actually runs. The Pro commands are
        // listed too, but each refuses to run (with a clear remedy) unless the
        // Pro edition is active, so they never touch a missing Pro class.
        $this->commands([
            SendCommand::class,
            StatusCommand::class,
            SummaryCommand::class,
            ReadCommand::class,
            CdrFetchCommand::class,
            DoctorCommand::class,
            InstallCommand::class,
            PruneCommand::class,
            CertInspectCommand::class,
            CertConvertCommand::class,
            DiagnoseCommand::class,
            XmlInspectCommand::class,
            XmlDiffCommand::class,
            SmartRetryCommand::class,
            CertificateExpiryCommand::class,
        ]);
    }

    private function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $proActive = $this->app->make(ProDetector::class)->isActive();
            new ScheduleRegistrar($this->app->make(Repository::class))->register($schedule, $proActive);
        });
    }

    private static function configString(Repository $config, string $key, string $default): string
    {
        $value = $config->get($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private static function configInt(Repository $config, string $key, int $default): int
    {
        $value = $config->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    private static function configFloat(Repository $config, string $key, float $default): float
    {
        $value = $config->get($key);

        return is_numeric($value) ? (float) $value : $default;
    }

    /** quipu's configured PSR-3 log channel, or the app default when unset. */
    private static function logChannel(Application $app): LoggerInterface
    {
        $config = $app->make(Repository::class);
        $channel = $config->get('quipu.logging.channel');
        $manager = $app->make('log');
        assert($manager instanceof LogManager);

        return $manager->channel(is_string($channel) && $channel !== '' ? $channel : null);
    }
}
