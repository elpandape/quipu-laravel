<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\QuipuLaravel\Console\QuipuCommand;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Shared plumbing for the Pro-only Artisan commands. They are registered
 * unconditionally (so `artisan list` shows them) but refuse to run when the Pro
 * edition is inactive, printing a clear remedy instead of failing on a missing
 * class. Every Pro class is therefore only referenced inside a handler that has
 * already passed {@see guardPro()}.
 */
abstract class ProCommand extends QuipuCommand
{
    /**
     * True when the Pro edition is inactive; also prints the remedy. Callers
     * return FAILURE straight after a true result, before touching any Pro type.
     */
    protected function guardPro(ProDetector $detector): bool
    {
        if ($detector->isActive()) {
            return false;
        }

        $this->error(
            'Esta función requiere la edición Pro de quipu (elpandape/quipu-pro). '
            . 'Instálela con "composer require elpandape/quipu-pro" y active quipu.pro = "auto" o true.',
        );

        return true;
    }

    /** Reads a file (honouring --disk/--path) from the given base folder, or null when absent. */
    protected function readFromDisk(FilesystemFactory $factory, string $file, string $defaultFolder = ''): ?string
    {
        $folder = $this->targetFolder($defaultFolder);
        $path = $folder === '' ? $file : rtrim($folder, '/') . '/' . $file;

        return $this->resolveDisk($factory)->get($path);
    }
}
