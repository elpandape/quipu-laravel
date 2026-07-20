<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\Quipu\Exception\InvalidDocumentException;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Xml\XmlComparator;
use ElPandaPe\QuipuPro\Xml\XmlDifference;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Structurally diffs two UBL XML files (Pro's XmlComparator, ds:Signature
 * ignored). Prints each semantic difference (path, kind, left/right) or reports
 * the two are equivalent. Files are read from --disk/--path.
 */
final class XmlDiffCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:xml:diff
        {a : Primer archivo XML}
        {b : Segundo archivo XML}
        {--disk= : Disco donde viven los archivos}
        {--path= : Carpeta donde viven los archivos}';

    /** @var string */
    protected $description = 'Compara dos XML UBL y muestra las diferencias estructurales.';

    public function handle(ProDetector $detector, FilesystemFactory $factory): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $left = $this->argumentString('a');
        $right = $this->argumentString('b');

        $leftXml = $this->readFromDisk($factory, $left);
        $rightXml = $this->readFromDisk($factory, $right);
        if ($leftXml === null || $rightXml === null) {
            $this->error('No se encontró alguno de los archivos a comparar.');

            return self::FAILURE;
        }

        try {
            $differences = new XmlComparator()->compare($leftXml, $rightXml);
        } catch (InvalidDocumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($differences === []) {
            $this->info('Los documentos son equivalentes.');

            return self::SUCCESS;
        }

        foreach ($differences as $difference) {
            $this->line($this->format($difference));
        }
        $this->warn(sprintf('%d diferencia(s).', count($differences)));

        return self::SUCCESS;
    }

    private function format(XmlDifference $difference): string
    {
        return sprintf(
            '%s [%s]: %s → %s',
            $difference->path,
            $difference->kind->name,
            $difference->left ?? '(ausente)',
            $difference->right ?? '(ausente)',
        );
    }
}
