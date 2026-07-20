<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Factory;

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Models\Ticket;

/**
 * Builds Ticket rows for the tests, with sane defaults so each test only spells
 * out the attributes it cares about.
 */
final class TicketFactory
{
    /**
     * @param array<string, mixed> $overrides
     */
    public static function create(array $overrides = []): Ticket
    {
        return Ticket::query()->create(array_merge([
            'tenant_id' => null,
            'ticket' => '1234567890',
            'document_type' => DocumentType::DailySummary,
            'state' => 'pending',
        ], $overrides));
    }
}
