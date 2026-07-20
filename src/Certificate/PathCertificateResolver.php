<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;

/**
 * Loads the certificate PEM from a local file. Dev-only: a local path does not
 * survive serverless / Laravel Cloud — use the inline or disk source there.
 */
final readonly class PathCertificateResolver implements CertificateResolver
{
    public function __construct(private string $path) {}

    public function resolvePem(): string
    {
        if ($this->path === '' || !is_file($this->path)) {
            throw new EmitterConfigException(sprintf('No se pudo leer el certificado del emisor en "%s".', $this->path));
        }

        // The is_file() guard already rejected a missing path; the cast only
        // absorbs the theoretical false (e.g. a race) without an unreachable branch.
        return (string) file_get_contents($this->path);
    }
}
