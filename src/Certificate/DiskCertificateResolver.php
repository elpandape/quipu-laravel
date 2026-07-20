<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Loads the certificate PEM from a Laravel filesystem disk (config
 * 'quipu.certificate.disk' + '.path') — e.g. S3 — so it persists on serverless
 * without living in an env var.
 */
final readonly class DiskCertificateResolver implements CertificateResolver
{
    public function __construct(private Filesystem $disk, private string $path) {}

    public function resolvePem(): string
    {
        if ($this->path === '') {
            throw new EmitterConfigException('La ruta del certificado en el disco (QUIPU_CERTIFICATE_PATH) no está configurada.');
        }

        $pem = $this->disk->get($this->path);

        if (!is_string($pem) || $pem === '') {
            throw new EmitterConfigException(sprintf('No se pudo leer el certificado del emisor en el disco en "%s".', $this->path));
        }

        return $pem;
    }
}
