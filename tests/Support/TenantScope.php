<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Tenancy\TenantKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Tenancy;

/**
 * Test-scenario helpers for the multi-tenant runtime: activate a stancl/spatie
 * tenant (config + container state, no database) so the container-resolved
 * DocumentDispatcher / CorrelativoManager / DocumentStorage see it as current,
 * and read back a series row's tenant scope.
 */
final class TenantScope
{
    /** Signed-XML path produced by the StubDocument on whichever disk is active. */
    public const string SIGNED_FILE = 'signed/20000000001-01-F001-1.xml';

    /** Make a stancl tenant (with its own disk) the initialised, active tenant. */
    public static function activateStancl(): void
    {
        config()->set('quipu.tenancy.driver', 'stancl');

        $tenancy = new Tenancy();
        $tenancy->tenant = new StanclTenantDouble();
        $tenancy->initialized = true;

        app()->instance(Tenancy::class, $tenancy);
    }

    /** Make a spatie tenant (with its own disk) the current tenant under $key. */
    public static function activateSpatie(int $key): void
    {
        config()->set('quipu.tenancy.driver', 'spatie');
        config()->set('multitenancy.current_tenant_container_key', 'currentTenant');
        config()->set('multitenancy.tenant_model', SpatieTenantDouble::class);

        $tenant = new SpatieTenantDouble();
        $tenant->id = $key;

        app()->instance('currentTenant', $tenant);
    }

    /** A model's primary key as the (non-null) string quipu scopes by. */
    public static function keyString(Model $model): string
    {
        $key = TenantKey::toString($model->getKey());
        assert($key !== null);

        return $key;
    }

    /** The tenant_id stored on the F001/01 series counter row, if any. */
    public static function seriesTenantId(): mixed
    {
        return DB::table('quipu_series')
            ->where('document_type', '01')
            ->where('series', 'F001')
            ->value('tenant_id');
    }

    /** A "tenants" table shaped for a stancl {@see StanclDbTenant} (string id). */
    public static function createStanclTenantsTable(): void
    {
        Schema::dropIfExists('tenants');
        Schema::create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
        });
    }

    /** A "tenants" table shaped for a spatie {@see SpatieTenantDouble} (auto id). */
    public static function createSpatieTenantsTable(): void
    {
        Schema::dropIfExists('tenants');
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
