<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Events\DocumentRejected;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\RecordingLogger;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

it('con Pro adjunta el diagnóstico al rechazo (evento y log)', function (): void {
    config()->set('quipu.pro', true);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::rejectedWithCode('2223');
    Event::fake();

    app(DocumentDispatcher::class)->dispatch(new StubDocument());

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

it('sin Pro el rechazo no lleva diagnóstico', function (): void {
    config()->set('quipu.pro', false);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::rejectedWithCode('2223');
    Event::fake();

    app(DocumentDispatcher::class)->dispatch(new StubDocument());

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

it('con Pro registra el diagnóstico de un fault y propaga la excepción', function (): void {
    config()->set('quipu.pro', true);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->sendBillError = new SunatFaultException('1033', 'La numeración fue registrada antes');
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());

    expect(fn(): mixed => $dispatcher->send($record))->toThrow(SunatFaultException::class);

    $faults = array_values(array_filter(
        $recording->records,
        static fn(array $rec): bool => $rec['message'] === 'SUNAT rechazó el envío con un fault.',
    ));
    expect($faults)->toHaveCount(1)
        ->and($faults[0]['context']['fault_code'])->toBe('1033')
        ->and(array_key_exists('remedy', $faults[0]['context']))->toBeTrue();
});

it('sin Pro un fault propaga sin registro de diagnóstico', function (): void {
    config()->set('quipu.pro', false);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));
    $fake = new FakeQuipu()->bind();
    $fake->sender->sendBillError = new SunatFaultException('1033', 'La numeración fue registrada antes');
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());

    expect(fn(): mixed => $dispatcher->send($record))->toThrow(SunatFaultException::class);

    $faults = array_filter(
        $recording->records,
        static fn(array $rec): bool => $rec['message'] === 'SUNAT rechazó el envío con un fault.',
    );
    expect($faults)->toBeEmpty();
});
