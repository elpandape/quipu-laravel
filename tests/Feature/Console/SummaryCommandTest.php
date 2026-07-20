<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Models\Ticket;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('envía un resumen del inbox y guarda el ticket', function (): void {
    $fake = new FakeQuipu(new StubDocument(DocumentType::DailySummary, '20000000001-RC-20260717-1'))->bind();
    $fake->sender->ticket = 'RC-TICKET-1';
    Storage::disk('local')->put('inbox/resumen.xml', '<SummaryDocuments/>');

    expect(Artisan::call('quipu:summary', ['--file' => 'resumen.xml']))->toBe(0)
        ->and(Artisan::output())->toContain('Ticket: RC-TICKET-1.');

    $ticket = Ticket::query()->where('ticket', 'RC-TICKET-1')->first();
    expect($ticket)->not->toBeNull()
        ->and($ticket?->document_type)->toBe(DocumentType::DailySummary)
        ->and($ticket?->state)->toBe(Ticket::STATE_PENDING);
});

it('exige el archivo con --file', function (): void {
    new FakeQuipu(new StubDocument(DocumentType::DailySummary))->bind();

    expect(Artisan::call('quipu:summary'))->toBe(1)
        ->and(Artisan::output())->toContain('Indique el archivo del resumen con --file.');
});

it('falla cuando el archivo no existe', function (): void {
    new FakeQuipu(new StubDocument(DocumentType::DailySummary))->bind();

    expect(Artisan::call('quipu:summary', ['--file' => 'nope.xml']))->toBe(1);
});

it('lee del disco y carpeta indicados por --disk/--path', function (): void {
    $fake = new FakeQuipu(new StubDocument(DocumentType::DailySummary, '20000000001-RC-20260717-1'))->bind();
    $fake->sender->ticket = 'RC-TICKET-1';
    Storage::fake('nube');
    Storage::disk('nube')->put('bandeja/r.xml', '<SummaryDocuments/>');

    expect(Artisan::call('quipu:summary', ['--file' => 'r.xml', '--disk' => 'nube', '--path' => 'bandeja']))->toBe(0);

    expect(Ticket::query()->where('ticket', 'RC-TICKET-1')->first())->not->toBeNull();
});
