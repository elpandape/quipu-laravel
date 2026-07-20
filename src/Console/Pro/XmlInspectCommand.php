<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\Quipu\Exception\InvalidDocumentException;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Xml\XmlInspector;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Queries a UBL XML file with an XPath expression (Pro's XmlInspector, namespace
 * aware). Prints every matching value and the match count. The file is read from
 * --disk/--path.
 */
final class XmlInspectCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:xml:inspect
        {file : Archivo XML a inspeccionar}
        {xpath : Expresión XPath a evaluar}
        {--disk= : Disco donde vive el archivo}
        {--path= : Carpeta donde vive el archivo}';

    /** @var string */
    protected $description = 'Evalúa una expresión XPath sobre un XML UBL y muestra las coincidencias.';

    public function handle(ProDetector $detector, FilesystemFactory $factory): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $file = $this->argumentString('file');
        $xpath = $this->argumentString('xpath');

        $xml = $this->readFromDisk($factory, $file);
        if ($xml === null) {
            $this->error(sprintf('No se encontró el archivo "%s".', $file));

            return self::FAILURE;
        }

        try {
            $values = new XmlInspector($xml)->values($xpath);
        } catch (InvalidDocumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($values === []) {
            $this->warn(sprintf('Sin coincidencias para «%s».', $xpath));

            return self::SUCCESS;
        }

        foreach ($values as $value) {
            $this->line($value);
        }
        $this->info(sprintf('%d coincidencia(s).', count($values)));

        return self::SUCCESS;
    }
}
