<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Shared plumbing for the package's Artisan commands: reading string options,
 * resolving the target disk (honouring --disk) and the target folder (honouring
 * --path) so every file command can be pointed at another disk (S3 included) or
 * folder without repeating the logic.
 */
abstract class QuipuCommand extends Command
{
    protected function optionString(string $name): ?string
    {
        $value = $this->option($name);
        if (!is_scalar($value) || is_bool($value)) {
            return null;
        }

        $string = (string) $value;

        return $string !== '' ? $string : null;
    }

    protected function argumentString(string $name): string
    {
        $value = $this->argument($name);

        return is_scalar($value) && !is_bool($value) ? (string) $value : '';
    }

    protected function resolveDisk(FilesystemFactory $factory): Filesystem
    {
        return $factory->disk($this->optionString('disk') ?? $this->configString('quipu.storage.disk', 'local'));
    }

    protected function targetFolder(string $default): string
    {
        return $this->optionString('path') ?? $default;
    }

    protected function configString(string $key, string $default): string
    {
        $value = config($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
