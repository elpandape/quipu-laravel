<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\HasQuipuEmitter;
use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;
use Illuminate\Database\Eloquent\Model;

/**
 * A host-application Tenant model wired to a quipu emitter purely by
 * `use HasQuipuEmitter` over the conventional columns, to exercise the trait's
 * default column mapping and the "encrypted" casts it merges in.
 */
final class QuipuEmitterTenant extends Model implements ProvidesQuipuEmitter
{
    use HasQuipuEmitter;

    public $timestamps = false;

    protected $table = 'app_tenants';

    /** @var array<string> */
    protected $guarded = [];
}
