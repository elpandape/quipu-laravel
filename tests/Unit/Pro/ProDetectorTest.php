<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Pro\ProUnavailableException;

// quipu-pro is a require-dev of this package, so class_exists(QuipuPro::class)
// is true in the test suite: fromConfig() reflects that.

it('está apagado cuando el flag es false', function (): void {
    expect(ProDetector::fromConfig(false)->isActive())->toBeFalse();
});

it('está encendido cuando el flag es true y Pro está instalado', function (): void {
    expect(ProDetector::fromConfig(true)->isActive())->toBeTrue();
});

it('auto-detecta Pro instalado', function (): void {
    expect(ProDetector::fromConfig('auto')->isActive())->toBeTrue();
});

it('normaliza los strings "true"/"false"', function (): void {
    expect(ProDetector::fromConfig('true')->isActive())->toBeTrue()
        ->and(ProDetector::fromConfig('false')->isActive())->toBeFalse();
});

it('trata cualquier otro valor como auto', function (): void {
    expect(ProDetector::fromConfig('sí')->isActive())->toBeTrue()
        ->and(ProDetector::fromConfig(123)->isActive())->toBeTrue();
});

it('falla claro cuando Pro está forzado pero ausente', function (): void {
    $detector = new ProDetector(true, installed: false);

    expect(fn(): bool => $detector->isActive())->toThrow(ProUnavailableException::class);
});

it('degrada a apagado cuando auto y Pro ausente', function (): void {
    expect(new ProDetector('auto', installed: false)->isActive())->toBeFalse();
});
