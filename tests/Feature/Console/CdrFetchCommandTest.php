<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\DocumentFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    config()->set('quipu.emisor.ruc', '20000000001');
});

it('re-descarga y guarda el CDR', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->cpeStatus->result = CdrFactory::consult(withCdr: true);
    $document = DocumentFactory::create(['document_type' => DocumentType::Invoice, 'series' => 'F001', 'number' => 1]);

    expect(Artisan::call('quipu:cdr:fetch', ['document' => $document->id]))->toBe(0);

    expect($document->fresh()?->cdr_path)->toBe('cdr/R-20000000001-01-F001-1.xml')
        ->and(Storage::disk('local')->exists('cdr/R-20000000001-01-F001-1.xml'))->toBeTrue();
});

it('falla si el comprobante no existe', function (): void {
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:cdr:fetch', ['document' => 999]))->toBe(1);
});

it('advierte cuando SUNAT no adjunta un CDR', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->cpeStatus->result = CdrFactory::consult(withCdr: false);
    $document = DocumentFactory::create();

    expect(Artisan::call('quipu:cdr:fetch', ['document' => $document->id]))->toBe(1)
        ->and(Artisan::output())->toContain('SUNAT no adjuntó un CDR');
});

it('reporta un fallo de transporte', function (): void {
    $fake = new FakeQuipu()->bind();
    $fake->cpeStatus->error = new TransportException('sin red');
    $document = DocumentFactory::create();

    expect(Artisan::call('quipu:cdr:fetch', ['document' => $document->id]))->toBe(1);
});

it('respeta --file, --disk y --path', function (): void {
    $fake = new FakeQuipu()->bind();
    Storage::fake('nube');
    $fake->cpeStatus->result = CdrFactory::consult(withCdr: true);
    $document = DocumentFactory::create(['series' => 'F001', 'number' => 1]);

    expect(Artisan::call('quipu:cdr:fetch', [
        'document' => $document->id,
        '--file' => 'mi-cdr.xml',
        '--disk' => 'nube',
        '--path' => 'cdrs',
    ]))->toBe(0);

    expect(Storage::disk('nube')->exists('cdrs/mi-cdr.xml'))->toBeTrue()
        ->and($document->fresh()?->cdr_path)->toBe('cdrs/mi-cdr.xml');
});
