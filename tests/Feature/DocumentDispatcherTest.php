<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Events\CdrReceived;
use ElPandaPe\QuipuLaravel\Events\DocumentAccepted;
use ElPandaPe\QuipuLaravel\Events\DocumentIssued;
use ElPandaPe\QuipuLaravel\Events\DocumentRejected;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
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

it('firma, persiste y almacena el XML dejando el comprobante en Signed', function (): void {
    new FakeQuipu()->bind();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    $record = $dispatcher->issue(new StubDocument());

    expect($record->state)->toBe(State::Signed)
        ->and($record->document_type)->toBe(DocumentType::Invoice)
        ->and($record->series)->toBe('F001')
        ->and($record->number)->toBe(1)
        ->and($record->signed_xml_path)->toBe('signed/20000000001-01-F001-1.xml')
        ->and($record->digest)->toBe('DIGEST==')
        ->and(Storage::disk('local')->exists('signed/20000000001-01-F001-1.xml'))->toBeTrue();
    Event::assertDispatched(DocumentIssued::class);
});

it('envía y registra un CDR aceptado, moviendo a Accepted', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::accepted();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    $record = $dispatcher->dispatch(new StubDocument());

    expect($record->state)->toBe(State::Accepted)
        ->and($record->sunat_status)->toBe('accepted')
        ->and($record->sunat_response_code)->toBe('0')
        ->and($record->cdr_path)->toBe('cdr/R-20000000001-01-F001-1.xml')
        ->and($fake->sender->sendBillCalls)->toBe(1)
        ->and(Storage::disk('local')->exists('cdr/R-20000000001-01-F001-1.xml'))->toBeTrue();
    Event::assertDispatched(CdrReceived::class);
    Event::assertDispatched(DocumentAccepted::class);
});

it('mueve a Observed ante un CDR con observaciones', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::observed();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    $record = $dispatcher->dispatch(new StubDocument());

    expect($record->state)->toBe(State::Observed)
        ->and($record->sunat_status)->toBe('accepted_with_observations');
    Event::assertDispatched(DocumentAccepted::class);
});

it('registra un rechazo como estado, sin lanzar excepción', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::rejected();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    $record = $dispatcher->dispatch(new StubDocument());

    expect($record->state)->toBe(State::Rejected)
        ->and($record->sunat_response_code)->toBe('2335');
    Event::assertDispatched(DocumentRejected::class);
    Event::assertNotDispatched(DocumentAccepted::class);
});

it('no guarda archivo de CDR cuando el CDR no trae XML', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::withoutXml();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);

    $record = $dispatcher->dispatch(new StubDocument());

    expect($record->cdr_path)->toBeNull()
        ->and($record->state)->toBe(State::Accepted);
});

it('propaga un fallo de transporte al enviar y deja el comprobante en Signed', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->sendBillError = new TransportException('SUNAT caído');
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());

    expect(fn(): mixed => $dispatcher->send($record))->toThrow(TransportException::class);
    expect($record->fresh()?->state)->toBe(State::Signed);
});
