<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Diagnosing\NullRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\ProRejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Idempotency\DatabaseResultStore;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuPro\Idempotency\ResultStore;
use ElPandaPe\QuipuPro\Retry\RetryPolicy;

beforeEach(function (): void {
    config()->set('quipu.certificate.source', 'path');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    config()->set('quipu.emisor.ruc', '20000000001');
    config()->set('quipu.emisor.sol_user', 'MODDATOS');
    config()->set('quipu.emisor.sol_pass', 'moddatos');
});

it('con Pro desactivado arma el emisor Lite y el diagnóstico nulo', function (): void {
    config()->set('quipu.pro', false);

    expect(app(ProDetector::class)->isActive())->toBeFalse()
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class)
        ->and(app(RejectionDiagnoser::class))->toBeInstanceOf(NullRejectionDiagnoser::class);
});

it('con Pro forzado compone el emisor resiliente, el store persistente y el diagnóstico Pro', function (): void {
    config()->set('quipu.pro', true);

    expect(app(ProDetector::class)->isActive())->toBeTrue()
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class)
        ->and(app(ResultStore::class))->toBeInstanceOf(DatabaseResultStore::class)
        ->and(app(RejectionDiagnoser::class))->toBeInstanceOf(ProRejectionDiagnoser::class);
});

it('con Pro en auto detecta el paquete instalado', function (): void {
    config()->set('quipu.pro', 'auto');

    expect(app(ProDetector::class)->isActive())->toBeTrue()
        ->and(app(Quipu::class))->toBeInstanceOf(Quipu::class);
});

it('construye la RetryPolicy desde la config', function (): void {
    config()->set('quipu.retry.max_attempts', 5);
    config()->set('quipu.retry.base_delay_ms', 250);
    config()->set('quipu.retry.factor', 3.5);
    config()->set('quipu.retry.cap_delay_ms', 9000);

    $policy = app(RetryPolicy::class);

    expect($policy->maxAttempts)->toBe(5)
        ->and($policy->baseDelayMs)->toBe(250)
        ->and($policy->factor)->toBe(3.5)
        ->and($policy->capDelayMs)->toBe(9000);
});

it('cae a los valores por defecto de la RetryPolicy ante una config no numérica', function (): void {
    config()->set('quipu.retry.max_attempts', 'x');
    config()->set('quipu.retry.base_delay_ms');
    config()->set('quipu.retry.factor', 'y');
    config()->set('quipu.retry.cap_delay_ms', []);

    $policy = app(RetryPolicy::class);

    expect($policy->maxAttempts)->toBe(3)
        ->and($policy->baseDelayMs)->toBe(1000)
        ->and($policy->factor)->toBe(2.0)
        ->and($policy->capDelayMs)->toBe(30000);
});
