<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Series\CorrelativoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('entrega correlativos consecutivos para una serie', function (): void {
    $manager = app(CorrelativoManager::class);

    expect($manager->next('01', 'F001'))->toBe(1)
        ->and($manager->next('01', 'F001'))->toBe(2)
        ->and($manager->next('01', 'F001'))->toBe(3);
});

it('mantiene contadores independientes por tipo, serie y tenant', function (): void {
    $manager = app(CorrelativoManager::class);

    expect($manager->next('01', 'F001'))->toBe(1)
        ->and($manager->next('01', 'F002'))->toBe(1)
        ->and($manager->next('03', 'B001'))->toBe(1)
        ->and($manager->next('01', 'F001', 'tenant-9'))->toBe(1)
        ->and($manager->next('01', 'F001'))->toBe(2)
        ->and($manager->next('01', 'F001', 'tenant-9'))->toBe(2);
});

it('no reparte números duplicados en llamadas sucesivas', function (): void {
    $manager = app(CorrelativoManager::class);

    $numbers = [];
    for ($i = 0; $i < 50; $i++) {
        $numbers[] = $manager->next('01', 'F001');
    }

    expect($numbers)->toBe(range(1, 50))
        ->and(array_unique($numbers))->toHaveCount(50);
});
