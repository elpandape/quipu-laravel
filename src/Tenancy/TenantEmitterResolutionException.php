<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use RuntimeException;

/**
 * Thrown when a tenancy driver is active but the active emitter cannot be
 * resolved from the current tenant: there is no tenant in scope, or the tenant
 * model does not implement {@see ProvidesQuipuEmitter}. Fails loud, with the
 * remedy, so a misconfigured tenant never silently falls back to the wrong
 * emitter.
 */
final class TenantEmitterResolutionException extends RuntimeException
{
    public static function noActiveTenant(string $driver): self
    {
        return new self(sprintf(
            'No hay un tenant activo para el driver de multi-tenant «%s»; inicialice la tenencia '
            . '(identifique el tenant) antes de emitir un comprobante.',
            $driver,
        ));
    }

    public static function doesNotProvideEmitter(string $driver, string $tenantClass): self
    {
        return new self(sprintf(
            'El tenant activo (%s) del driver «%s» no implementa %s; impleméntelo para exponer el RUC, '
            . 'la razón social, las credenciales SOL y el certificado PEM del emisor.',
            $tenantClass,
            $driver,
            ProvidesQuipuEmitter::class,
        ));
    }
}
