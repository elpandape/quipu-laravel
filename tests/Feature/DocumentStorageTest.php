<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Exception\DocumentStorageException;
use ElPandaPe\QuipuLaravel\Storage\DocumentStorage;
use Illuminate\Support\Facades\Storage;

it('guarda y lee el XML firmado bajo signed/', function (): void {
    $disk = Storage::fake('local');
    $storage = app(DocumentStorage::class);

    $path = $storage->putSignedXml('F001-1.xml', '<Invoice/>');

    expect($path)->toBe('signed/F001-1.xml')
        ->and($storage->getSignedXml($path))->toBe('<Invoice/>');
    $disk->assertExists('signed/F001-1.xml');
});

it('guarda y lee el CDR bajo cdr/', function (): void {
    Storage::fake('local');
    $storage = app(DocumentStorage::class);

    $path = $storage->putCdr('R-F001-1.zip', 'cdr-bytes');

    expect($path)->toBe('cdr/R-F001-1.zip')
        ->and($storage->getCdr($path))->toBe('cdr-bytes');
});

it('lista y lee los archivos del inbox/', function (): void {
    $disk = Storage::fake('local');
    $disk->put('inbox/a.xml', 'A');
    $disk->put('inbox/b.xml', 'B');
    $storage = app(DocumentStorage::class);

    expect($storage->listInbox())->toHaveCount(2)
        ->and($storage->readInbox('a.xml'))->toBe('A')
        ->and($storage->readInbox('b.xml'))->toBe('B');
});

it('escribe en el disco configurado en quipu.storage.disk (no solo local)', function (): void {
    config()->set('quipu.storage.disk', 'nube');
    $disk = Storage::fake('nube');
    $storage = app(DocumentStorage::class);

    $storage->putSignedXml('F001-2.xml', 'X');

    $disk->assertExists('signed/F001-2.xml');
});

it('cae al disco local cuando el nombre configurado no es una cadena válida', function (): void {
    config()->set('quipu.storage.disk');
    $disk = Storage::fake('local');
    $storage = app(DocumentStorage::class);

    $storage->putSignedXml('F001-3.xml', 'X');

    $disk->assertExists('signed/F001-3.xml');
});

it('falla al leer un archivo inexistente', function (): void {
    Storage::fake('local');
    $storage = app(DocumentStorage::class);

    expect(fn() => $storage->getCdr('cdr/missing.zip'))
        ->toThrow(DocumentStorageException::class);
});
