<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\PublishedAssets;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

// quipu:install publishes into the (testbench) application directories, which
// are auto-loaded; the published files are cleaned up around the test so the
// rest of the suite does not double-load the migrations.
beforeEach(fn() => PublishedAssets::clean());

afterEach(fn() => PublishedAssets::clean());

it('publica la configuración y las migraciones', function (): void {
    expect(Artisan::call('quipu:install'))->toBe(0)
        ->and(Artisan::output())->toContain('Publicados la configuración (quipu-config) y las migraciones (quipu-migrations).');

    expect(File::exists(config_path('quipu.php')))->toBeTrue()
        ->and(File::glob(database_path('migrations/*create_quipu_tickets_table.php')))->not->toBeEmpty();
});
