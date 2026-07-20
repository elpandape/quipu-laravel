<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Jobs\SendDocumentJob;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('informa cuando no hay comprobantes pendientes', function (): void {
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:send'))->toBe(0)
        ->and(Artisan::output())->toContain('No hay comprobantes pendientes de envío.');
});

it('encola los comprobantes firmados pendientes', function (): void {
    new FakeQuipu()->bind();
    Queue::fake();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $dispatcher->issue(new StubDocument());

    expect(Artisan::call('quipu:send'))->toBe(0);

    Queue::assertPushed(SendDocumentJob::class);
});

it('envía de forma síncrona con --sync', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->billCdr = CdrFactory::accepted();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());

    expect(Artisan::call('quipu:send', ['--sync' => true]))->toBe(0);

    expect($record->fresh()?->state)->toBe(State::Accepted);
});

it('reporta un fallo de transporte en --sync sin mover el estado', function (): void {
    $fake = new FakeQuipu()->bind();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $record = $dispatcher->issue(new StubDocument());
    $fake->sender->sendBillError = new TransportException('SUNAT no responde');

    expect(Artisan::call('quipu:send', ['--sync' => true]))->toBe(0);

    expect($record->fresh()?->state)->toBe(State::Signed);
});

it('envía solo el comprobante indicado por --id', function (): void {
    new FakeQuipu()->bind();
    Queue::fake();
    /** @var DocumentDispatcher $dispatcher */
    $dispatcher = app(DocumentDispatcher::class);
    $first = $dispatcher->issue(new StubDocument(fileName: '20000000001-01-F001-1'));
    $dispatcher->issue(new StubDocument(fileName: '20000000001-01-F001-2'));

    expect(Artisan::call('quipu:send', ['--id' => $first->id]))->toBe(0);

    Queue::assertPushed(SendDocumentJob::class, 1);
});
