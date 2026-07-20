<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Jobs\SendDocumentJob;
use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('envía el comprobante firmado y lo resuelve', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::accepted();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());

    new SendDocumentJob($record->id)->handle($dispatcher);

    expect($record->fresh()?->state)->toBe(State::Accepted);
});

it('ignora un comprobante inexistente', function (): void {
    new FakeQuipu()->bind();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    new SendDocumentJob(999)->handle($dispatcher);

    expect(Document::query()->get())->toBeEmpty();
});

it('ignora un comprobante que ya no está en Signed', function (): void {
    $fake = new FakeQuipu()->bind();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $document = DocumentFactory::create(['state' => State::Accepted]);

    new SendDocumentJob($document->id)->handle($dispatcher);

    expect($document->fresh()?->state)->toBe(State::Accepted)
        ->and($fake->sender->sendBillCalls)->toBe(0);
});

it('usa la conexión de colas configurada', function (): void {
    config()->set('quipu.queue.connection', 'redis');

    expect(new SendDocumentJob(1)->connection)->toBe('redis');
});

it('usa la conexión por defecto cuando no se configura', function (): void {
    expect(new SendDocumentJob(1)->connection)->toBeNull();
});
