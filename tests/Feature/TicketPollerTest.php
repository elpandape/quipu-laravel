<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Dispatching\TicketPoller;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Events\DocumentAccepted;
use ElPandaPe\QuipuLaravel\Events\DocumentRejected;
use ElPandaPe\QuipuLaravel\Events\DocumentVoided;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\TicketFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\RecordingLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('resuelve un ticket aceptado y acepta los comprobantes cubiertos', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create(['document_type' => DocumentType::DailySummary]);
    $document = DocumentFactory::create(['state' => State::Sent, 'ticket_id' => $ticket->id]);

    expect($poller->poll($ticket))->toBeTrue()
        ->and($ticket->fresh()?->state)->toBe('accepted')
        ->and($document->fresh()?->state)->toBe(State::Accepted)
        ->and(Storage::disk('local')->exists('cdr/R-' . $ticket->ticket . '.xml'))->toBeTrue();
    Event::assertDispatched(DocumentAccepted::class);
});

it('acepta con observaciones los comprobantes cubiertos', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::observed();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['state' => State::Sent, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    expect($document->fresh()?->state)->toBe(State::Observed);
});

it('marca como Voided los comprobantes de una comunicación de baja aceptada', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create(['document_type' => DocumentType::VoidedDocuments]);
    $document = DocumentFactory::create(['state' => State::Accepted, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    expect($document->fresh()?->state)->toBe(State::Voided);
    Event::assertDispatched(DocumentVoided::class);
});

it('rechaza los comprobantes cubiertos ante un CDR rechazado', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::rejected();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['state' => State::Sent, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    expect($document->fresh()?->state)->toBe(State::Rejected)
        ->and($ticket->fresh()?->state)->toBe('rejected');
    Event::assertDispatched(DocumentRejected::class);
});

it('con Pro adjunta el diagnóstico al rechazo asíncrono (evento y log)', function (): void {
    config()->set('quipu.pro', true);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::rejectedWithCode('2223');
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['state' => State::Sent, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    expect($document->fresh()?->state)->toBe(State::Rejected);
    Event::assertDispatched(
        DocumentRejected::class,
        static fn(DocumentRejected $event): bool => $event->diagnosis instanceof RejectionReport
            && $event->diagnosis->code === 2223
            && $event->diagnosis->retryable === false,
    );
    $rejection = array_values(array_filter(
        $recording->records,
        static fn(array $record): bool => $record['message'] === 'SUNAT rechazó el comprobante.',
    ));
    expect($rejection)->toHaveCount(1)
        ->and($rejection[0]['context']['action'] ?? null)->toContain('No reenviar');
});

it('sin Pro el rechazo asíncrono no lleva diagnóstico', function (): void {
    config()->set('quipu.pro', false);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::rejectedWithCode('2223');
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['state' => State::Sent, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    Event::assertDispatched(
        DocumentRejected::class,
        static fn(DocumentRejected $event): bool => !$event->diagnosis instanceof RejectionReport,
    );
    $rejection = array_values(array_filter(
        $recording->records,
        static fn(array $record): bool => $record['message'] === 'SUNAT rechazó el comprobante.',
    ));
    expect($rejection)->toHaveCount(1)
        ->and(array_key_exists('action', $rejection[0]['context']))->toBeFalse();
});

it('omite un comprobante que no admite la transición', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();
    $document = DocumentFactory::create(['state' => State::Rejected, 'ticket_id' => $ticket->id]);

    $poller->poll($ticket);

    expect($document->fresh()?->state)->toBe(State::Rejected);
    Event::assertNotDispatched(DocumentAccepted::class);
});

it('no guarda CDR cuando el ticket no trae XML', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::withoutXml();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();

    $poller->poll($ticket);

    expect(Storage::disk('local')->exists('cdr/R-' . $ticket->ticket . '.xml'))->toBeFalse()
        ->and($ticket->fresh()?->state)->toBe('accepted');
});

it('devuelve false cuando el ticket sigue en proceso', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusError = new TransportException('The summary is still being processed.');
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();

    expect($poller->poll($ticket))->toBeFalse()
        ->and($ticket->fresh()?->state)->toBe('pending');
});
