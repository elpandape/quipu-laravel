<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tenancy\TenantKey;

it('convierte claves escalares a string y las no escalares a null', function (): void {
    expect(TenantKey::toString('acme'))->toBe('acme')
        ->and(TenantKey::toString(42))->toBe('42')
        ->and(TenantKey::toString(null))->toBeNull()
        ->and(TenantKey::toString([1]))->toBeNull();
});
