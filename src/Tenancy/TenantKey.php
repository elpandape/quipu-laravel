<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

/**
 * Normalises a tenancy package's tenant key — which its contracts expose as an
 * untyped (mixed) value that is in practice an int or string primary key — to
 * the string quipu scopes persistence and correlativos by. A non-scalar (or
 * absent) key becomes null, i.e. "no tenant in scope".
 */
final class TenantKey
{
    public static function toString(mixed $key): ?string
    {
        return is_scalar($key) ? (string) $key : null;
    }
}
