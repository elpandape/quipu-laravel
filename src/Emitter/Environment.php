<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\Quipu\Ws\SoapEndpoints;

/**
 * The SUNAT environment the emitter targets, mapped to the matching Lite
 * SoapEndpoints preset.
 */
enum Environment: string
{
    case Beta = 'beta';
    case Production = 'produccion';

    public function endpoints(): SoapEndpoints
    {
        return match ($this) {
            self::Beta => SoapEndpoints::beta(),
            self::Production => SoapEndpoints::production(),
        };
    }
}
