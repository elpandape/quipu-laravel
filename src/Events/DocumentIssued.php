<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Events;

use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Fired once a document has been built, signed and persisted (its signed XML is
 * stored and it has reached the Signed state), before it is reported to SUNAT.
 */
final readonly class DocumentIssued
{
    public function __construct(public Document $document) {}
}
