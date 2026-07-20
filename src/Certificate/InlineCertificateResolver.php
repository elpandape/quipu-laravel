<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;

/**
 * Loads the certificate PEM from a base64-encoded env var (config
 * 'quipu.certificate.inline' / QUIPU_CERT_PEM). The PEM is base64-encoded so
 * its newlines survive an env var; mono-tenant cloud installs keep the whole
 * certificate in configuration this way.
 */
final readonly class InlineCertificateResolver implements CertificateResolver
{
    public function __construct(private string $base64) {}

    public function resolvePem(): string
    {
        if ($this->base64 === '') {
            throw new EmitterConfigException('El certificado inline (QUIPU_CERT_PEM) no está configurado.');
        }

        $pem = base64_decode($this->base64, true);

        if ($pem === false || $pem === '') {
            throw new EmitterConfigException('El certificado inline (QUIPU_CERT_PEM) no es base64 válido.');
        }

        return $pem;
    }
}
