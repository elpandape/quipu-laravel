<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Diagnosing;

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Result\CdrResult;

/**
 * No-op RejectionDiagnoser used when the Pro edition is inactive: it never
 * produces a diagnosis, so the send flow behaves exactly as the base install.
 */
final readonly class NullRejectionDiagnoser implements RejectionDiagnoser
{
    public function forCdr(CdrResult $cdr): ?RejectionReport
    {
        return null;
    }

    public function forFault(SunatFaultException $fault): ?RejectionReport
    {
        return null;
    }
}
