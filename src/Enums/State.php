<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Enums;

/**
 * Lifecycle state of a persisted document, and the state machine that governs
 * which transitions are legal. Real emission (build/sign/send) lives in a later
 * phase; this only models the states a document moves through.
 */
enum State: string
{
    case Draft = 'draft';
    case Signed = 'signed';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Observed = 'observed';
    case Rejected = 'rejected';
    case Voided = 'voided';

    /**
     * The states this one may legally move to.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Signed],
            self::Signed => [self::Sent],
            self::Sent => [self::Accepted, self::Observed, self::Rejected],
            self::Accepted => [self::Voided],
            self::Observed => [self::Voided],
            self::Rejected, self::Voided => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
