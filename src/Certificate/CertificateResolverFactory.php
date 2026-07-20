<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Picks the CertificateResolver driver from config('quipu.certificate.source').
 * The base install ships "path", "inline" and "disk"; a later Pro phase plugs a
 * tenant-scoped "database" source into this same seam.
 */
final readonly class CertificateResolverFactory
{
    public function __construct(
        private Repository $config,
        private FilesystemFactory $filesystem,
    ) {}

    public function make(): CertificateResolver
    {
        $source = $this->string('quipu.certificate.source');

        return match ($source) {
            'path' => new PathCertificateResolver($this->string('quipu.certificate.path')),
            'inline' => new InlineCertificateResolver($this->string('quipu.certificate.inline')),
            'disk' => new DiskCertificateResolver(
                $this->filesystem->disk($this->diskName()),
                $this->string('quipu.certificate.path'),
            ),
            default => throw new EmitterConfigException(sprintf('La fuente del certificado "%s" no es válida; use "path", "inline" o "disk".', $source)),
        };
    }

    private function string(string $key): string
    {
        $value = $this->config->get($key);

        return is_string($value) ? $value : '';
    }

    /** The configured disk name, or null to fall back to the default disk. */
    private function diskName(): ?string
    {
        $name = $this->string('quipu.certificate.disk');

        return $name !== '' ? $name : null;
    }
}
