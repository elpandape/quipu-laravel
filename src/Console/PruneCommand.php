<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Prunes the inbox of XML older than --days (default 30). Only the inbox is
 * touched — signed XML and CDR are kept, since SUNAT requires retaining CPE.
 */
final class PruneCommand extends QuipuCommand
{
    /** @var string */
    protected $signature = 'quipu:prune
        {--days=30 : Antigüedad mínima en días para eliminar}
        {--disk= : Disco a podar}
        {--path= : Carpeta a podar}';

    /** @var string */
    protected $description = 'Elimina del inbox los XML más antiguos que N días (nunca signed/ ni cdr/).';

    public function handle(FilesystemFactory $factory): int
    {
        $days = max(0, (int) ($this->optionString('days') ?? '0'));
        $disk = $this->resolveDisk($factory);
        $folder = $this->targetFolder($this->configString('quipu.storage.paths.inbox', 'inbox'));
        $threshold = CarbonImmutable::now()->subDays($days)->getTimestamp();

        $removed = 0;
        foreach ($disk->files($folder) as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
                $removed++;
            }
        }

        $this->info(sprintf('Podados %d archivo(s) del inbox.', $removed));

        return self::SUCCESS;
    }
}
