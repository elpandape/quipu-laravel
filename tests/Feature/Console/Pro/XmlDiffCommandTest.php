<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\XmlFixtures;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('a.xml', XmlFixtures::invoice('F001-1'));
    Storage::disk('local')->put('b.xml', XmlFixtures::invoice('F001-1'));
    Storage::disk('local')->put('c.xml', XmlFixtures::invoice('F001-2'));
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:xml:diff', ['a' => 'a.xml', 'b' => 'b.xml']))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('reporta cuando los documentos son equivalentes', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:xml:diff', ['a' => 'a.xml', 'b' => 'b.xml']))->toBe(0)
        ->and(Artisan::output())->toContain('equivalentes');
});

it('muestra las diferencias estructurales', function (): void {
    config()->set('quipu.pro', true);

    $exit = Artisan::call('quipu:xml:diff', ['a' => 'a.xml', 'b' => 'c.xml']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('F001-1')
        ->and($output)->toContain('F001-2')
        ->and($output)->toContain('diferencia');
});

it('falla cuando falta alguno de los archivos', function (): void {
    config()->set('quipu.pro', true);

    expect(Artisan::call('quipu:xml:diff', ['a' => 'a.xml', 'b' => 'noexiste.xml']))->toBe(1)
        ->and(Artisan::output())->toContain('No se encontró');
});

it('falla cuando un XML no se puede interpretar', function (): void {
    config()->set('quipu.pro', true);
    Storage::disk('local')->put('roto.xml', 'no soy xml <');

    expect(Artisan::call('quipu:xml:diff', ['a' => 'a.xml', 'b' => 'roto.xml']))->toBe(1)
        ->and(Artisan::output())->toContain('no se pudo interpretar');
});
