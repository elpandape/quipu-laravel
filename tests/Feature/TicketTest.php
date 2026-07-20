<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\TicketFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relaciona un ticket con los comprobantes que cubre', function (): void {
    $ticket = TicketFactory::create(['document_type' => DocumentType::DailySummary]);
    DocumentFactory::create(['series' => 'B001', 'number' => 1, 'ticket_id' => $ticket->id]);
    $second = DocumentFactory::create(['series' => 'B001', 'number' => 2, 'ticket_id' => $ticket->id]);

    $ticket->load('documents');

    expect($ticket->document_type)->toBe(DocumentType::DailySummary)
        ->and($ticket->documents)->toHaveCount(2)
        ->and($second->ticket)->not->toBeNull()
        ->and($second->ticket?->id)->toBe($ticket->id);
});
