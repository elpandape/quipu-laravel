<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;
use Spatie\Multitenancy\Models\Tenant;

/**
 * A spatie/laravel-multitenancy Tenant model that also implements
 * ProvidesQuipuEmitter, exposing a fixed emitter (RUC, credentials and Lite's
 * test certificate PEM) so the SpatieTenantResolver can be exercised without a
 * database. Extending spatie's model keeps Tenant::current()'s ?static return
 * type satisfied when this instance is the current tenant.
 *
 * @property int $id
 */
final class SpatieTenantDouble extends Tenant implements ProvidesQuipuEmitter
{
    protected $table = 'tenants';

    public function quipuRuc(): string
    {
        return '20655443322';
    }

    public function quipuLegalName(): string
    {
        return 'TENANT SPATIE SAC';
    }

    public function quipuTradeName(): string
    {
        return 'SPATIE';
    }

    public function quipuSolUser(): string
    {
        return 'SPTUSER';
    }

    public function quipuSolPass(): string
    {
        return 'sptpass';
    }

    public function quipuCertificatePem(): string
    {
        return CertificateFile::plainPem();
    }

    public function quipuCertificatePassphrase(): ?string
    {
        return null;
    }

    public function quipuIgvRate(): ?float
    {
        return null; // falls back to the global config('quipu.igv_rate')
    }

    public function quipuSeriesPrefix(): string
    {
        return 'B';
    }

    public function quipuStorageDisk(): string
    {
        return 'spatie-tenant-disk';
    }
}
