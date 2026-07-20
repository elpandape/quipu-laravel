<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * A database-backed stancl/tenancy Tenant that also implements
 * ProvidesQuipuEmitter, so Quipu::forTenant can resolve it by key through
 * stancl's own find()/initialize() and the StanclTenantContext can read its
 * emitter/disk. Uses a string primary key ("id") on the conventional "tenants"
 * table.
 *
 * @property string $id
 */
final class StanclDbTenant extends Model implements ProvidesQuipuEmitter, Tenant
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'tenants';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    /** @var array<string> */
    protected $guarded = [];

    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): string
    {
        $id = $this->getAttribute('id');
        assert(is_string($id));

        return $id;
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

    public function quipuIgvRate(): ?float
    {
        return null;
    }

    public function quipuSeriesPrefix(): string
    {
        return 'F';
    }

    public function quipuStorageDisk(): string
    {
        return 'stancl-tenant-disk';
    }
}
