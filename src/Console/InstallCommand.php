<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use Illuminate\Console\Command;

/**
 * Publishes the package's configuration (quipu-config) and migrations
 * (quipu-migrations) into the host application.
 */
final class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'quipu:install';

    /** @var string */
    protected $description = 'Publica la configuración y las migraciones de quipu.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'quipu-config']);
        $this->call('vendor:publish', ['--tag' => 'quipu-migrations']);

        $this->info('Publicados la configuración (quipu-config) y las migraciones (quipu-migrations).');

        return self::SUCCESS;
    }
}
