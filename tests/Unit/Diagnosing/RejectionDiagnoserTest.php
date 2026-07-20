<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\QuipuLaravel\Diagnosing\NullRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\ProRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;

it('el diagnóstico nulo nunca produce un reporte', function (): void {
    $diagnoser = new NullRejectionDiagnoser();

    expect($diagnoser->forCdr(new CdrResult(CdrStatus::Rejected, '2223', 'x')))->toBeNull()
        ->and($diagnoser->forFault(new SunatFaultException('102', 'x')))->toBeNull();
});

it('el diagnóstico Pro mapea un CDR rechazado a un RejectionReport accionable', function (): void {
    $report = new ProRejectionDiagnoser()->forCdr(new CdrResult(CdrStatus::Rejected, '2223', 'x'));

    expect($report)->toBeInstanceOf(RejectionReport::class)
        ->and($report->code)->toBe(2223)
        ->and($report->severity)->toBe('rejection')
        ->and($report->retryable)->toBeFalse()
        ->and($report->action)->toContain('No reenviar');
});

it('el diagnóstico Pro mapea un fault transitorio como reintentable', function (): void {
    $report = new ProRejectionDiagnoser()->forFault(new SunatFaultException('102', 'x'));

    expect($report)->toBeInstanceOf(RejectionReport::class)
        ->and($report->code)->toBe(102)
        ->and($report->severity)->toBe('exception')
        ->and($report->retryable)->toBeTrue();
});
