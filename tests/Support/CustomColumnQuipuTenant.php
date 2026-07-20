<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\HasQuipuEmitter;
use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;
use Illuminate\Database\Eloquent\Model;

/**
 * A host Tenant model that renames some quipu columns by overriding the trait's
 * *Column() methods, to prove HasQuipuEmitter honours custom column names —
 * including for an "encrypted" column (cert).
 */
final class CustomColumnQuipuTenant extends Model implements ProvidesQuipuEmitter
{
    use HasQuipuEmitter;

    public $timestamps = false;

    protected $table = 'org';

    /** @var array<string> */
    protected $guarded = [];

    protected function quipuRucColumn(): string
    {
        return 'ruc';
    }

    protected function quipuCertificateColumn(): string
    {
        return 'cert';
    }

    protected function quipuDiskColumn(): string
    {
        return 'storage_disk';
    }
}
