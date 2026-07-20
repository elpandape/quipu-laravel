<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use RuntimeException;

/**
 * Thrown by the {@see TenancyEmitterResolverFactory} when config
 * quipu.tenancy.driver cannot select a resolver: an unrecognised driver, or
 * "auto" with neither supported tenancy package installed. Fails loud, with the
 * remedy, instead of silently ignoring the setting. The "none", "stancl",
 * "spatie" and custom-class drivers never reach here.
 */
final class TenancyNotImplementedException extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'El driver de multi-tenant «%s» no se reconoce. Use "none" (mono-tenant), "stancl", "spatie", '
            . '"auto", o el nombre de una clase que implemente %s.',
            $driver,
            EmitterConfigResolver::class,
        ));
    }

    public static function autoWithoutPackage(): self
    {
        return new self(
            'config quipu.tenancy.driver = "auto" pero no hay ningún paquete de tenancy instalado. '
            . 'Instale stancl/tenancy o spatie/laravel-multitenancy, o use "none" (mono-tenant).',
        );
    }

    public static function forTenantWithoutDriver(): self
    {
        return new self(
            'Quipu::forTenant requiere un driver de multi-tenant; config quipu.tenancy.driver = "none" '
            . '(mono-tenant). Configure "stancl", "spatie" o "auto" para cambiar de tenant.',
        );
    }
}
