<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Diagnosing;

/**
 * Framework-facing, Pro-agnostic reading of a SUNAT rejection: the official
 * SUNAT message, the severity band, and Pro's actionable next step (action,
 * remedy, whether the same submission may be retried). A stable core value
 * object so consumers react to a diagnosis without depending on Pro's own
 * Diagnosis type; the Pro adapter maps ErrorDiagnosis into this.
 */
final readonly class RejectionReport
{
    public function __construct(
        public int $code,
        public ?string $sunatMessage,
        public string $severity,
        public string $action,
        public string $remedy,
        public bool $retryable,
    ) {}
}
