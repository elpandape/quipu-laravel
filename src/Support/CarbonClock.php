<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Support;

use Carbon\CarbonImmutable;
use ElPandaPe\Quipu\Contract\Clock;

/**
 * Lite {@see Clock} backed by Carbon's clock, so certificate pre-flight and the
 * expiration alert read "now" through the same instant the rest of the Laravel
 * app does — and tests can freeze it with CarbonImmutable::setTestNow().
 */
final readonly class CarbonClock implements Clock
{
    public function now(): int
    {
        return CarbonImmutable::now()->getTimestamp();
    }
}
