<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Exception\InvalidStateTransitionException;
use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persiste un comprobante con sus casts de dominio y estado inicial', function (): void {
    $document = DocumentFactory::create([
        'document_type' => DocumentType::Receipt,
        'series' => 'B001',
        'number' => 7,
    ]);

    $fresh = Document::query()->findOrFail($document->id);

    expect($fresh->document_type)->toBe(DocumentType::Receipt)
        ->and($fresh->series)->toBe('B001')
        ->and($fresh->number)->toBe(7)
        ->and($fresh->state)->toBe(State::Draft)
        ->and($fresh->tenant_id)->toBeNull()
        ->and($fresh->issued_at)->toBeInstanceOf(CarbonImmutable::class);
});

it('rechaza un correlativo duplicado con la clave única', function (): void {
    DocumentFactory::create(['tenant_id' => 't-1', 'series' => 'F001', 'number' => 5]);

    expect(fn(): \ElPandaPe\QuipuLaravel\Models\Document => DocumentFactory::create(['tenant_id' => 't-1', 'series' => 'F001', 'number' => 5]))
        ->toThrow(QueryException::class);
});

it('avanza por transiciones válidas y las persiste', function (): void {
    $document = DocumentFactory::create();

    $document->transitionTo(State::Signed);
    $document->transitionTo(State::Sent);
    $document->transitionTo(State::Accepted);

    expect($document->state)->toBe(State::Accepted)
        ->and(Document::query()->findOrFail($document->id)->state)->toBe(State::Accepted);
});

it('rechaza una transición inválida con una excepción clara y no la persiste', function (): void {
    $document = DocumentFactory::create();

    expect(fn() => $document->transitionTo(State::Accepted))
        ->toThrow(InvalidStateTransitionException::class, 'No se puede pasar del estado "draft" al estado "accepted".');

    expect($document->fresh()?->state)->toBe(State::Draft);
});
