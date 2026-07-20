<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\InvalidDocumentException;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeReader;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('lee un XML del inbox y muestra el tipo y el nombre', function (): void {
    new FakeQuipu()->bind();
    Storage::disk('local')->put('inbox/f.xml', '<Invoice/>');

    $exit = Artisan::call('quipu:read', ['file' => 'f.xml']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('FACTURA ELECTRÓNICA')
        ->and($output)->toContain('20000000001-01-F001-1');
});

it('falla cuando el archivo no existe', function (): void {
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:read', ['file' => 'x.xml']))->toBe(1);
});

it('reporta un XML que no se puede leer', function (): void {
    $fake = new FakeQuipu();
    $fake->reader = new FakeReader(new StubDocument(), new InvalidDocumentException('XML corrupto'));
    $fake->bind();
    Storage::disk('local')->put('inbox/roto.xml', '<broken>');

    expect(Artisan::call('quipu:read', ['file' => 'roto.xml']))->toBe(1)
        ->and(Artisan::output())->toContain('No se pudo leer el XML');
});
