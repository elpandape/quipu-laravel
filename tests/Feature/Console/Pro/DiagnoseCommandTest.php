<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:diagnose', ['code' => '2223']))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('diagnostica un código de rechazo', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:diagnose', ['code' => '2223']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('Código 2223')
        ->and($output)->toContain('No reenviar');
});

it('exige un código o un --id', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:diagnose'))->toBe(1)
        ->and(Artisan::output())->toContain('Indique un código');
});

it('falla cuando el comprobante del --id no existe', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:diagnose', ['--id' => '999']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró el comprobante');
});

it('falla cuando el comprobante aún no tiene código de respuesta', function (): void {
    config()->set('quipu.pro', true);
    $document = DocumentFactory::create();

    expect(Artisan::call('quipu:diagnose', ['--id' => (string) $document->id]))->toBe(1)
        ->and(Artisan::output())->toContain('aún no tiene');
});

it('diagnostica el código de respuesta de un comprobante persistido', function (): void {
    config()->set('quipu.pro', true);
    $document = DocumentFactory::create(['state' => State::Rejected, 'sunat_response_code' => '2223']);

    expect(Artisan::call('quipu:diagnose', ['--id' => (string) $document->id]))->toBe(0)
        ->and(Artisan::output())->toContain('Código 2223');
});
