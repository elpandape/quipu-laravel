<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Events;

use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Fired when a document is confirmed voided before SUNAT, i.e. the ticket of the
 * comunicación de baja (RA) that covers it resolved accepted.
 */
final readonly class DocumentVoided
{
    public function __construct(public Document $document) {}
}
