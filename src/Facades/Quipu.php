<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Facades;

use Closure;
use ElPandaPe\Quipu\Catalog\CreditNoteType;
use ElPandaPe\Quipu\Catalog\DebitNoteType;
use ElPandaPe\Quipu\Model\Client;
use ElPandaPe\Quipu\Quipu as QuipuEmitter;
use ElPandaPe\Quipu\Result\BillResult;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\QuipuLaravel\Builder\ProBuilders;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContext;
use ElPandaPe\QuipuLaravel\Testing\QuipuFake;
use ElPandaPe\QuipuPro\Builder\FluentInvoiceBuilder;
use ElPandaPe\QuipuPro\Builder\FluentNoteBuilder;
use ElPandaPe\QuipuPro\QuipuPro;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use RuntimeException;

/**
 * Facade over the container-bound Lite Quipu emitter, so consumers can call
 * Quipu::emitInvoice(...) without resolving it by hand.
 *
 * The invoice()/creditNote()/debitNote() helpers expose Pro's tax engine: they
 * return the fluent builders already seeded with the configured issuer, so the
 * app supplies the minimum and Pro computes IGV/ISC/etc. They require the Pro
 * edition; without it they raise a clear ProUnavailableException.
 *
 * @method static list<string> validate(\ElPandaPe\Quipu\Contract\Document $document)
 * @method static void assertValid(\ElPandaPe\Quipu\Contract\Document $document)
 * @method static SignedXml sign(\ElPandaPe\Quipu\Contract\Document $document)
 * @method static BillResult emit(\ElPandaPe\Quipu\Contract\Document $document)
 * @method static BillResult emitInvoice(\ElPandaPe\Quipu\Model\Invoice $invoice)
 * @method static BillResult sendBill(SignedXml $signedXml)
 *
 * @see QuipuEmitter
 */
final class Quipu extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QuipuEmitter::class;
    }

    /**
     * Run $callback with $tenantKey made the active tenant (via the configured
     * tenancy driver's own API), restoring the previously active tenant when it
     * returns or throws. Requires a multi-tenant driver; with "none" it raises a
     * clear TenancyNotImplementedException.
     *
     * @template TReturn
     *
     * @param Closure(): TReturn $callback
     * @return TReturn
     */
    public static function forTenant(string $tenantKey, Closure $callback): mixed
    {
        $container = self::getFacadeApplication();
        assert($container !== null);

        // The switched tenant may sign with its own certificate/RUC, so drop the
        // cached emitter singletons (and this facade's resolved-instance cache) both
        // entering and leaving the tenant, forcing a fresh resolution on each side.
        return $container->make(TenantContext::class)->forTenant($tenantKey, static function () use ($callback): mixed {
            self::forgetEmitter();

            try {
                return $callback();
            } finally {
                self::forgetEmitter();
            }
        });
    }

    /**
     * Drop the container's cached emitter singletons and this facade's own resolved
     * instance, so the next resolution rebuilds the emitter from the currently
     * active tenant's config instead of returning one bound to a previous tenant.
     */
    private static function forgetEmitter(): void
    {
        $container = Container::getInstance();
        $container->forgetInstance(QuipuEmitter::class);
        $container->forgetInstance(QuipuPro::class);
        self::clearResolvedInstance(QuipuEmitter::class);
    }

    /** Pro: a fluent invoice builder pre-seeded with the configured issuer. */
    public static function invoice(Client $client): FluentInvoiceBuilder
    {
        return app(ProBuilders::class)->invoice($client);
    }

    /** Pro: a fluent credit-note builder pre-seeded with the configured issuer. */
    public static function creditNote(Client $client, CreditNoteType $reason = CreditNoteType::OtherConcepts): FluentNoteBuilder
    {
        return app(ProBuilders::class)->creditNote($client, $reason);
    }

    /** Pro: a fluent debit-note builder pre-seeded with the configured issuer. */
    public static function debitNote(Client $client, DebitNoteType $reason = DebitNoteType::PenaltiesOrOtherCharges): FluentNoteBuilder
    {
        return app(ProBuilders::class)->debitNote($client, $reason);
    }

    /**
     * Swap the emitter for offline in-memory doubles so tests never touch a
     * certificate or SUNAT — the Laravel idiom, like Mail::fake(). Reuses Pro's
     * shippable testing toolkit when the Pro edition is active. Returns the fake
     * handle for configuring responses (acceptsEverything/rejectsEverything/…).
     */
    public static function fake(): QuipuFake
    {
        $container = self::getFacadeApplication();
        assert($container !== null);

        $fake = QuipuFake::bind($container, $container->make(ProDetector::class));
        self::swap($container->make(QuipuEmitter::class));

        return $fake;
    }

    /**
     * Assert at least one document was sent to SUNAT (optionally matching the
     * callback). Requires Quipu::fake() to have been called first.
     *
     * @param (callable(SignedXml): bool)|null $callback
     */
    public static function assertSent(?callable $callback = null): void
    {
        self::resolveFake()->assertSent($callback);
    }

    /** Assert nothing was sent to SUNAT. Requires Quipu::fake() first. */
    public static function assertNothingSent(): void
    {
        self::resolveFake()->assertNothingSent();
    }

    /** Assert exactly this many documents were sent to SUNAT. Requires Quipu::fake() first. */
    public static function assertSentCount(int $count): void
    {
        self::resolveFake()->assertSentCount($count);
    }

    private static function resolveFake(): QuipuFake
    {
        $container = self::getFacadeApplication();
        assert($container !== null);

        if (!$container->bound(QuipuFake::class)) {
            throw new RuntimeException('Llame a Quipu::fake() antes de usar las aserciones de envío (assertSent/assertNothingSent/assertSentCount).');
        }

        return $container->make(QuipuFake::class);
    }
}
