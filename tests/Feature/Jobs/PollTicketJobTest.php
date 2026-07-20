<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Dispatching\TicketPoller;
use ElPandaPe\QuipuLaravel\Jobs\PollTicketJob;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\TicketFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('resuelve el ticket', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();

    new PollTicketJob($ticket->id)->handle($poller);

    expect($ticket->fresh()?->state)->toBe('accepted');
});

it('se libera cuando el ticket sigue en proceso', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusError = new TransportException('The summary is still being processed.');
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);
    $ticket = TicketFactory::create();

    new PollTicketJob($ticket->id)->handle($poller);

    expect($ticket->fresh()?->state)->toBe('pending');
});

it('ignora un ticket inexistente', function (): void {
    new FakeQuipu()->bind();
    /** @var TicketPoller $poller */
    $poller = app(TicketPoller::class);

    new PollTicketJob(999)->handle($poller);

    expect(true)->toBeTrue();
});

it('usa la conexión de colas configurada', function (): void {
    config()->set('quipu.queue.connection', 'sqs');

    expect(new PollTicketJob(1)->connection)->toBe('sqs');
});
