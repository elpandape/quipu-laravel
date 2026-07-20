<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\XmlFixtures;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('doc.xml', XmlFixtures::invoice());
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:xml:inspect', ['file' => 'doc.xml', 'xpath' => '//cbc:ID']))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('evalúa un XPath y muestra las coincidencias', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:xml:inspect', ['file' => 'doc.xml', 'xpath' => '//cbc:ID']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('F001-1')
        ->and($output)->toContain('1 coincidencia');
});

it('reporta cuando no hay coincidencias', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:xml:inspect', ['file' => 'doc.xml', 'xpath' => '//cbc:NoExiste']))->toBe(0)
        ->and(Artisan::output())->toContain('Sin coincidencias');
});

it('falla cuando el archivo no existe', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:xml:inspect', ['file' => 'noexiste.xml', 'xpath' => '//cbc:ID']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró');
});

it('falla cuando el XML no se puede interpretar', function (): void {
    config()->set('quipu.pro', true);
    Storage::disk('local')->put('roto.xml', 'no soy xml <');

    expect(Artisan::call('quipu:xml:inspect', ['file' => 'roto.xml', 'xpath' => '//cbc:ID']))->toBe(1)
        ->and(Artisan::output())->toContain('no se pudo interpretar');
});
