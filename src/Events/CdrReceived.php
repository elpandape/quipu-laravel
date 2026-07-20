<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Events;

use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Fired whenever SUNAT's CDR (Constancia de Recepción) is recorded against a
 * document, regardless of its outcome. The more specific DocumentAccepted /
 * DocumentRejected events follow with the resolved state.
 */
final readonly class CdrReceived
{
    public function __construct(
        public Document $document,
        public CdrResult $cdr,
    ) {}
}
