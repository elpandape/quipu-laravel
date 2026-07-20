<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Series\CorrelativoManager;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\TicketFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Configure custom table names before migrating so the whole stack (migrations,
// models, correlativo) is exercised against a non-default schema.
beforeEach(function (): void {
    config()->set('quipu.tables.documents', 'cpe_comprobantes');
    config()->set('quipu.tables.tickets', 'cpe_tickets');
    config()->set('quipu.tables.series', 'cpe_series');

    expect(Artisan::call('migrate'))->toBe(0);
});

it('crea las tablas con los nombres configurados', function (): void {
    expect(Schema::hasTable('cpe_comprobantes'))->toBeTrue()
        ->and(Schema::hasTable('cpe_tickets'))->toBeTrue()
        ->and(Schema::hasTable('cpe_series'))->toBeTrue()
        ->and(Schema::hasTable('quipu_documents'))->toBeFalse()
        ->and(Schema::hasTable('quipu_tickets'))->toBeFalse()
        ->and(Schema::hasTable('quipu_series'))->toBeFalse();
});

it('persiste los modelos y su relación en las tablas configuradas', function (): void {
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['ticket_id' => $ticket->id]);

    expect($document->getTable())->toBe('cpe_comprobantes')
        ->and($ticket->getTable())->toBe('cpe_tickets')
        ->and(DB::table('cpe_comprobantes')->count())->toBe(1)
        ->and(DB::table('cpe_tickets')->count())->toBe(1)
        ->and($document->ticket?->is($ticket))->toBeTrue()
        ->and($ticket->documents->first()?->is($document))->toBeTrue();
});

it('entrega correlativos usando la tabla de series configurada', function (): void {
    $manager = app(CorrelativoManager::class);

    expect($manager->next('01', 'F001'))->toBe(1)
        ->and($manager->next('01', 'F001'))->toBe(2)
        ->and(DB::table('cpe_series')->where('series', 'F001')->value('last_number'))->toBe(2);
});
