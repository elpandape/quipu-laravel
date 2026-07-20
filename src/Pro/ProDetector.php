<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Pro;

use ElPandaPe\QuipuPro\QuipuPro;

/**
 * Decides whether the Pro edition is active by combining the config flag
 * (config('quipu.pro')) with whether elpandape/quipu-pro is actually installed:
 *   - false        → always off.
 *   - true         → forced on; if the package is missing, {@see isActive()}
 *                    throws {@see ProUnavailableException} instead of degrading.
 *   - "auto" (any) → on when the QuipuPro facade class exists, off otherwise.
 *
 * The install check is a plain constructor value so the decision stays pure and
 * fully testable; the service provider fills it with class_exists() at resolve
 * time via {@see fromConfig()}.
 */
final readonly class ProDetector
{
    public function __construct(
        private bool|string $flag,
        private bool $installed,
    ) {}

    /** Builds the detector from a raw config value, probing the package once. */
    public static function fromConfig(mixed $flag): self
    {
        return new self(self::normalize($flag), class_exists(QuipuPro::class));
    }

    public function isActive(): bool
    {
        if ($this->flag === false) {
            return false;
        }

        if ($this->flag === true && !$this->installed) {
            throw ProUnavailableException::forced();
        }

        return $this->installed;
    }

    private static function normalize(mixed $flag): bool|string
    {
        if (is_bool($flag)) {
            return $flag;
        }

        return match (is_string($flag) ? strtolower(trim($flag)) : 'auto') {
            'true' => true,
            'false' => false,
            default => 'auto',
        };
    }
}
