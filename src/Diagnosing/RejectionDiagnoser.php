<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Diagnosing;

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Result\CdrResult;

/**
 * Turns a SUNAT rejection (a rejected CDR or a synchronous SOAP fault) into an
 * actionable RejectionReport, or null when no diagnosis is available. The Pro
 * edition supplies a real implementation over ErrorDiagnosis; without Pro a
 * null-object returns null and the flow is unchanged.
 */
interface RejectionDiagnoser
{
    public function forCdr(CdrResult $cdr): ?RejectionReport;

    public function forFault(SunatFaultException $fault): ?RejectionReport;
}
