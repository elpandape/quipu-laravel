<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\TicketFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    Event::fake();
});

it('consulta un ticket por su valor', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    $ticket = TicketFactory::create(['ticket' => 'TCK-9']);

    expect(Artisan::call('quipu:status', ['ticket' => 'TCK-9']))->toBe(0);

    expect($ticket->fresh()?->state)->toBe('accepted');
});

it('sondea los pendientes cuando no se indica ticket', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusCdr = CdrFactory::accepted();
    $ticket = TicketFactory::create(['ticket' => 'A', 'state' => 'pending']);

    expect(Artisan::call('quipu:status'))->toBe(0);

    expect($ticket->fresh()?->state)->toBe('accepted');
});

it('reporta cuando el ticket no existe', function (): void {
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:status', ['ticket' => 'NO-EXISTE']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró el ticket "NO-EXISTE".');
});

it('informa cuando no hay tickets pendientes', function (): void {
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:status'))->toBe(0)
        ->and(Artisan::output())->toContain('No hay tickets pendientes por consultar.');
});

it('reporta un ticket aún en proceso', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->sender->statusError = new TransportException('The summary is still being processed.');
    TicketFactory::create(['ticket' => 'B', 'state' => 'pending']);

    expect(Artisan::call('quipu:status'))->toBe(0)
        ->and(Artisan::output())->toContain('aún en proceso');
});
