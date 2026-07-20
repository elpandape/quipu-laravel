<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use ElPandaPe\Quipu\Exception\InvalidDocumentException;
use ElPandaPe\Quipu\Quipu;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Parses a UBL XML file back into its typed model and prints a short summary
 * (document type and SUNAT file name). The file is read from the inbox by
 * default, or the disk/folder given by --disk/--path.
 */
final class ReadCommand extends QuipuCommand
{
    /** @var string */
    protected $signature = 'quipu:read
        {file : Nombre del archivo XML a leer}
        {--disk= : Disco donde vive el archivo}
        {--path= : Carpeta donde vive el archivo}';

    /** @var string */
    protected $description = 'Lee un XML UBL y muestra el tipo de comprobante y su nombre.';

    public function handle(Quipu $quipu, FilesystemFactory $factory): int
    {
        $file = $this->argumentString('file');
        $path = $this->targetFolder($this->configString('quipu.storage.paths.inbox', 'inbox')) . '/' . $file;

        $xml = $this->resolveDisk($factory)->get($path);
        if ($xml === null) {
            $this->error(sprintf('No se encontró el archivo "%s".', $path));

            return self::FAILURE;
        }

        try {
            $document = $quipu->read($xml);
        } catch (InvalidDocumentException $exception) {
            $this->error(sprintf('No se pudo leer el XML: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $this->info(sprintf('Tipo: %s', $document->documentType()->label()));
        $this->line(sprintf('Nombre: %s', $document->fileName()));

        return self::SUCCESS;
    }
}
