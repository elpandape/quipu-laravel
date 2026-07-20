<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuPro\Certificate\CertificateInspector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('inbox/cert.pfx', CertificateFile::pfxBytes());
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:cert:convert', ['pfx' => 'cert.pfx', '--path' => 'inbox']))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('convierte un .pfx en el PEM combinado y lo guarda', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:cert:convert', [
        'pfx' => 'cert.pfx',
        '--path' => 'inbox',
        '--out' => 'cert.pem',
        '--password' => CertificateFile::PFX_PASSWORD,
    ]);

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('PEM guardado')
        ->and(Storage::disk('local')->exists('inbox/cert.pem'))->toBeTrue();

    $pem = (string) Storage::disk('local')->get('inbox/cert.pem');
    expect(new CertificateInspector()->inspect($pem)->subjectRuc)->toBe('20000000001');
});

it('usa el nombre del pfx cuando no se indica --out', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:cert:convert', [
        'pfx' => 'cert.pfx',
        '--path' => 'inbox',
        '--password' => CertificateFile::PFX_PASSWORD,
    ]);

    expect($exit)->toBe(0)
        ->and(Storage::disk('local')->exists('inbox/cert.pem'))->toBeTrue();
});

it('falla con la contraseña incorrecta', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:cert:convert', ['pfx' => 'cert.pfx', '--path' => 'inbox', '--password' => 'mala']))->toBe(1)
        ->and(Artisan::output())->toContain('Conversión:');
});

it('falla cuando el .pfx no existe', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:cert:convert', ['pfx' => 'noexiste.pfx', '--path' => 'inbox']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró');
});
