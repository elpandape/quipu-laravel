<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Events;

use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Fired when SUNAT accepts a document — cleanly (Accepted) or with observations
 * (Observed). The CDR carries the observation notes, if any.
 */
final readonly class DocumentAccepted
{
    public function __construct(
        public Document $document,
        public CdrResult $cdr,
    ) {}
}
