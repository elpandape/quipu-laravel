<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Series\CorrelativoManager;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use ElPandaPe\QuipuLaravel\Tests\Support\TenantScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('bajo el tenant activo de stancl persiste el tenant_id y usa su disco', function (): void {
    TenantScope::activateStancl();
    Storage::fake('stancl-tenant-disk');
    new FakeQuipu()->bind();

    $record = app(DocumentDispatcher::class)->issue(new StubDocument());

    expect($record->tenant_id)->toBe('stancl-tenant')
        ->and(Storage::disk('stancl-tenant-disk')->exists(TenantScope::SIGNED_FILE))->toBeTrue();
});

it('escopa el correlativo por el tenant activo de stancl', function (): void {
    TenantScope::activateStancl();

    expect(app(CorrelativoManager::class)->next('01', 'F001'))->toBe(1)
        ->and(TenantScope::seriesTenantId())->toBe('stancl-tenant');
});

it('bajo el tenant actual de spatie persiste el tenant_id y usa su disco', function (): void {
    TenantScope::activateSpatie(42);
    Storage::fake('spatie-tenant-disk');
    new FakeQuipu()->bind();

    $record = app(DocumentDispatcher::class)->issue(new StubDocument());

    expect($record->tenant_id)->toBe('42')
        ->and(Storage::disk('spatie-tenant-disk')->exists(TenantScope::SIGNED_FILE))->toBeTrue();
});

it('escopa el correlativo por el tenant actual de spatie', function (): void {
    TenantScope::activateSpatie(42);

    expect(app(CorrelativoManager::class)->next('01', 'F001'))->toBe(1)
        ->and(TenantScope::seriesTenantId())->toBe('42');
});

it('un tenant_id explícito prevalece sobre el contexto activo', function (): void {
    TenantScope::activateStancl();
    Storage::fake('stancl-tenant-disk');
    new FakeQuipu()->bind();

    $record = app(DocumentDispatcher::class)->issue(new StubDocument(), 'manual-tenant');

    expect($record->tenant_id)->toBe('manual-tenant');
});
