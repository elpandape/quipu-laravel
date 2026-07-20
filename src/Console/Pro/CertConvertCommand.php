<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Certificate\CertificateConverter;
use ElPandaPe\QuipuPro\Certificate\CertificateException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Converts a SUNAT PKCS#12 (.pfx/.p12) into the passphrase-less combined PEM that
 * the signer consumes (Pro's CertificateConverter) and stores it on the disk.
 * The .pfx is read from --disk/--path; the PEM is written next to it (or to
 * --out). The PEM is never logged.
 */
final class CertConvertCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:cert:convert
        {pfx : Archivo .pfx/.p12 a convertir}
        {--password= : Contraseña del PKCS#12}
        {--out= : Nombre del PEM de salida (por defecto, el del .pfx con extensión .pem)}
        {--disk= : Disco donde vive el archivo}
        {--path= : Carpeta donde viven los archivos}';

    /** @var string */
    protected $description = 'Convierte un certificado .pfx/.p12 en el PEM combinado (certificado + clave).';

    public function handle(ProDetector $detector, FilesystemFactory $factory): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $pfx = $this->argumentString('pfx');
        $bytes = $this->readFromDisk($factory, $pfx);
        if ($bytes === null) {
            $this->error(sprintf('No se encontró el archivo "%s".', $pfx));

            return self::FAILURE;
        }

        try {
            $pem = new CertificateConverter()->pfxToPem($bytes, $this->optionString('password') ?? '');
        } catch (CertificateException $exception) {
            $this->error(sprintf('Conversión: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $outPath = $this->outPath($pfx);
        $this->resolveDisk($factory)->put($outPath, $pem);
        $this->info(sprintf('PEM guardado en "%s".', $outPath));

        return self::SUCCESS;
    }

    private function outPath(string $pfx): string
    {
        $out = $this->optionString('out') ?? pathinfo($pfx, PATHINFO_FILENAME) . '.pem';
        $folder = $this->targetFolder('');

        return $folder === '' ? $out : rtrim($folder, '/') . '/' . $out;
    }
}
