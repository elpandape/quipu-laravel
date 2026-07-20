<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Certificate\CertificateException;
use ElPandaPe\QuipuPro\Certificate\CertificateInfo;
use ElPandaPe\QuipuPro\Certificate\CertificateInspector;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Prints the salient facts of a signing certificate (Pro's CertificateInspector):
 * subject/RUC, issuer, serial, validity window, whether a private key travels
 * with it and the key size. Reads the configured certificate by default, or a PEM
 * file from --disk/--path when a file argument is given.
 */
final class CertInspectCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:cert:inspect
        {file? : Archivo PEM a inspeccionar (por defecto, el certificado configurado)}
        {--disk= : Disco donde vive el archivo}
        {--path= : Carpeta donde vive el archivo}';

    /** @var string */
    protected $description = 'Inspecciona un certificado PEM y muestra su RUC, vigencia y tamaño de clave.';

    public function handle(ProDetector $detector, CertificateResolver $resolver, FilesystemFactory $factory): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $pem = $this->pem($resolver, $factory);
        if ($pem === null) {
            return self::FAILURE;
        }

        try {
            $info = new CertificateInspector()->inspect($pem);
        } catch (CertificateException $exception) {
            $this->error(sprintf('Certificado: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $this->render($info);

        return self::SUCCESS;
    }

    private function pem(CertificateResolver $resolver, FilesystemFactory $factory): ?string
    {
        $file = $this->argumentString('file');

        if ($file !== '') {
            $pem = $this->readFromDisk($factory, $file);
            if ($pem === null) {
                $this->error(sprintf('No se encontró el archivo "%s".', $file));
            }

            return $pem;
        }

        try {
            return $resolver->resolvePem();
        } catch (EmitterConfigException $exception) {
            $this->error(sprintf('Certificado: %s', $exception->getMessage()));

            return null;
        }
    }

    private function render(CertificateInfo $info): void
    {
        $this->info(sprintf('Titular (CN): %s', $info->commonName));
        $this->line(sprintf('RUC: %s', $info->subjectRuc ?? '(no determinado)'));
        $this->line(sprintf('Emisor: %s', $info->issuer));
        $this->line(sprintf('Serie: %s', $info->serialNumber));
        $this->line(sprintf('Vigencia: %s a %s', gmdate('Y-m-d', $info->notBefore), gmdate('Y-m-d', $info->notAfter)));
        $this->line(sprintf('Clave privada: %s', $info->hasPrivateKey ? 'sí' : 'no'));
        $this->line(sprintf('Tamaño de clave: %s bits', $info->keyBits ?? '(desconocido)'));
    }
}
