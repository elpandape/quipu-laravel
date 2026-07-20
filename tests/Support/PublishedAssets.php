<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use Illuminate\Support\Facades\File;

/**
 * Cleans up the files quipu:install publishes into the (testbench) application
 * directories. Those dirs are auto-loaded, so leaving published migrations
 * behind would make the rest of the suite double-load them.
 */
final class PublishedAssets
{
    public static function clean(): void
    {
        $migrations = glob(database_path('migrations/*create_quipu_*table.php'));

        foreach ($migrations !== false ? $migrations : [] as $migration) {
            File::delete($migration);
        }

        File::delete(config_path('quipu.php'));
    }
}
