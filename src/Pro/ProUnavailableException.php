<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Pro;

use RuntimeException;

/**
 * Thrown when the Pro edition is forced on (config('quipu.pro') === true) but
 * the elpandape/quipu-pro package is not installed. Fails loud and early with a
 * remedy instead of silently degrading to the Lite emitter.
 */
final class ProUnavailableException extends RuntimeException
{
    public static function forced(): self
    {
        return new self(
            'La edición Pro de quipu está forzada (config quipu.pro = true) pero el paquete '
            . 'elpandape/quipu-pro no está instalado. Instálelo con "composer require elpandape/quipu-pro" '
            . 'o use quipu.pro = "auto" (detección automática) o false.',
        );
    }

    /** A Pro-only capability was used while the Pro edition is inactive. */
    public static function forFeature(string $feature): self
    {
        return new self(sprintf(
            '%s requiere la edición Pro de quipu (elpandape/quipu-pro), que no está instalada o está '
            . 'desactivada. Instálela con "composer require elpandape/quipu-pro" y active quipu.pro = "auto" o true.',
            $feature,
        ));
    }
}
