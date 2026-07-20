<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use Stancl\Tenancy\Contracts\Tenant;

/**
 * A stancl/tenancy Tenant that does NOT implement ProvidesQuipuEmitter, to
 * exercise the StanclTenantResolver's "tenant does not provide the emitter"
 * failure path.
 */
final class StanclBareTenant implements Tenant
{
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey(): string
    {
        return 'bare-tenant';
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
