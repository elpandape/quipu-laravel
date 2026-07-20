<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * A stancl/tenancy Tenant that also implements ProvidesQuipuEmitter, exposing a
 * fixed emitter (RUC, credentials and Lite's test certificate PEM) so the
 * StanclTenantResolver can be exercised without a database. The Tenant-contract
 * methods are inert stubs — the resolver only reads the emitter accessors.
 */
final class StanclTenantDouble implements ProvidesQuipuEmitter, Tenant
{
    public function quipuRuc(): string
    {
        return '20544332211';
    }

    public function quipuLegalName(): string
    {
        return 'TENANT STANCL SAC';
    }

    public function quipuTradeName(): string
    {
        return 'STANCL';
    }

    public function quipuSolUser(): string
    {
        return 'STNCLUSER';
    }

    public function quipuSolPass(): string
    {
        return 'stnclpass';
    }

    public function quipuCertificatePem(): string
    {
        return CertificateFile::plainPem();
    }

    public function quipuCertificatePassphrase(): ?string
    {
        return null;
    }

    public function quipuIgvRate(): float
    {
        return 8.0; // this tenant runs under the 8% MYPE regime
    }

    public function quipuSeriesPrefix(): string
    {
        return 'F';
    }

    public function quipuStorageDisk(): string
    {
        return 'stancl-tenant-disk';
    }

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): string
    {
        return 'stancl-tenant';
    }

    public function getInternal(string $key): mixed
    {
        return null;
    }

    public function setInternal(string $key, mixed $value): static
    {
        return $this;
    }

    public function run(callable $callback): mixed
    {
        return $callback();
    }
}
