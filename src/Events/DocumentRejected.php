<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Events;

use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Fired when SUNAT rejects a document. Rejection is a lifecycle state, not an
 * exception: the CDR's responseCode and description explain the reason. When the
 * Pro edition is active, $diagnosis carries the actionable reading of the
 * rejection (action, remedy, retryable); it stays null on a base install.
 */
final readonly class DocumentRejected
{
    public function __construct(
        public Document $document,
        public CdrResult $cdr,
        public ?RejectionReport $diagnosis = null,
    ) {}
}
