<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Exception;

use ElPandaPe\QuipuLaravel\Enums\State;
use RuntimeException;

/**
 * Thrown when a document is asked to move to a state its current state does not
 * allow (for example jumping from Draft straight to Accepted).
 */
final class InvalidStateTransitionException extends RuntimeException
{
    public function __construct(
        public readonly State $from,
        public readonly State $to,
    ) {
        parent::__construct(sprintf(
            'No se puede pasar del estado "%s" al estado "%s".',
            $from->value,
            $to->value,
        ));
    }
}
