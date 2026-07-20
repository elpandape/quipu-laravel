<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Diagnosing;

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\QuipuPro\Error\Diagnosis;
use ElPandaPe\QuipuPro\Error\ErrorDiagnosis;

/**
 * Pro RejectionDiagnoser: delegates to Pro's ErrorDiagnosis (official SUNAT
 * message + severity band + action/remedy/retryable playbook) and maps its
 * Diagnosis into the framework-facing RejectionReport, so nothing outside this
 * adapter depends on Pro's type. Only instantiated when the Pro edition is
 * active, so referencing the Pro classes here is safe.
 */
final readonly class ProRejectionDiagnoser implements RejectionDiagnoser
{
    public function __construct(private ErrorDiagnosis $diagnosis = new ErrorDiagnosis()) {}

    public function forCdr(CdrResult $cdr): RejectionReport
    {
        return $this->toReport($this->diagnosis->forCdr($cdr));
    }

    public function forFault(SunatFaultException $fault): RejectionReport
    {
        return $this->toReport($this->diagnosis->forFault($fault));
    }

    private function toReport(Diagnosis $diagnosis): RejectionReport
    {
        return new RejectionReport(
            code: $diagnosis->code,
            sunatMessage: $diagnosis->sunatMessage,
            severity: $diagnosis->severity->value,
            action: $diagnosis->action,
            remedy: $diagnosis->remedy,
            retryable: $diagnosis->retryable,
        );
    }
}
