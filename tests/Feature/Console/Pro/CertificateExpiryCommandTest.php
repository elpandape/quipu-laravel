<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuLaravel\Tests\Support\RecordingLogger;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    config()->set('quipu.certificate.path', CertificateFile::plain());
    CarbonImmutable::setTestNow('2026-07-20');
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:cert:alert'))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('reporta que el certificado está dentro del margen', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:cert:alert'))->toBe(0)
        ->and(Artisan::output())->toContain('dentro del margen');
});

it('advierte cuando el certificado está por vencer dentro del umbral', function (): void {
    config()->set('quipu.pro', true);
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));

    $exit = Artisan::call('quipu:cert:alert', ['--days' => '100000']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('vence en')
        ->and($output)->not->toContain('dentro del margen');
    expect($recording->records)->toHaveCount(1)
        ->and($recording->records[0]['level'])->toBe('warning');
});

it('alerta cuando el certificado ya venció', function (): void {
    config()->set('quipu.pro', true);
    CarbonImmutable::setTestNow('2037-01-01');
    $recording = new RecordingLogger();
    app()->instance(QuipuLogger::class, new QuipuLogger($recording));

    expect(Artisan::call('quipu:cert:alert'))->toBe(0)
        ->and(Artisan::output())->toContain('venció el');
    expect($recording->records)->toHaveCount(1)
        ->and($recording->records[0]['level'])->toBe('error');
});

it('falla cuando no se puede leer el certificado', function (): void {
    config()->set('quipu.pro', true);
    config()->set('quipu.certificate.path', CertificateFile::missing());

    expect(Artisan::call('quipu:cert:alert'))->toBe(1)
        ->and(Artisan::output())->toContain('Certificado:');
});
