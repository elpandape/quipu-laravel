<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('quipu.emisor.ruc', '20000000001');
    config()->set('quipu.emisor.sol_user', 'MODDATOS');
    config()->set('quipu.emisor.sol_pass', 'moddatos');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    Http::fake(['*' => Http::response('ok', 200)]);
});

it('con Pro añade el pre-vuelo del certificado cuando todo cuadra', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:doctor');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('Pre-vuelo: el certificado cumple los requisitos de firma de SUNAT.');
});

it('con Pro el pre-vuelo falla cuando el RUC del certificado no coincide con el emisor', function (): void {
    config()->set('quipu.pro', true);
    config()->set('quipu.emisor.ruc', '20999999999');

    $exit = Artisan::call('quipu:doctor');
    $output = Artisan::output();

    expect($exit)->toBe(1)
        ->and($output)->toContain('Pre-vuelo:')
        ->and($output)->toContain('no coincide');
});

it('sin Pro el doctor básico no ejecuta el pre-vuelo', function (): void {
    config()->set('quipu.pro', false);

    $exit = Artisan::call('quipu:doctor');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->not->toContain('Pre-vuelo');
});
