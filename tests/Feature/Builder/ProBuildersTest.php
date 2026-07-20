<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Model\Invoice;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Facades\Quipu;
use ElPandaPe\QuipuLaravel\Pro\ProUnavailableException;
use ElPandaPe\QuipuLaravel\Tests\Factory\ClientFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuPro\Builder\FluentInvoiceBuilder;
use ElPandaPe\QuipuPro\Builder\FluentNoteBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('quipu.emisor.ruc', '20000000001');
    config()->set('quipu.emisor.legal_name', 'ACME CORP SAC');
    config()->set('quipu.emisor.sol_user', 'MODDATOS');
    config()->set('quipu.emisor.sol_pass', 'moddatos');
    config()->set('quipu.certificate.source', 'path');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    Storage::fake('local');
});

it('sin Pro la fachada de facturación falla con un mensaje claro', function (): void {
    config()->set('quipu.pro', false);

    expect(fn(): FluentInvoiceBuilder => Quipu::invoice(ClientFactory::make()))
        ->toThrow(ProUnavailableException::class, 'requiere la edición Pro');
});

it('con Pro expone los constructores fluidos sembrados con el emisor', function (): void {
    config()->set('quipu.pro', true);

    $invoice = Quipu::invoice(ClientFactory::make())
        ->series('F001')
        ->number('1')
        ->cash()
        ->addLine('P001', 'Producto', 1.0, 100.0)
        ->build();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->company->ruc)->toBe('20000000001')
        ->and($invoice->company->legalName)->toBe('ACME CORP SAC')
        ->and(Quipu::creditNote(ClientFactory::make()))->toBeInstanceOf(FluentNoteBuilder::class)
        ->and(Quipu::debitNote(ClientFactory::make()))->toBeInstanceOf(FluentNoteBuilder::class);
});

it('siembra los constructores con la tasa de IGV configurada', function (): void {
    config()->set('quipu.pro', true);
    config()->set('quipu.igv_rate', 8.0);

    $invoice = Quipu::invoice(ClientFactory::make())
        ->series('F001')
        ->number('1')
        ->cash()
        ->addLine('P001', 'Menú', 1.0, 100.0)
        ->build();

    expect($invoice->details[0]->igvPercentage)->toBe(8.0)
        ->and($invoice->details[0]->igvAmount)->toBe(8.0)
        ->and($invoice->igvAmount)->toBe(8.0)
        ->and($invoice->totalAmount)->toBe(108.0);
});

it('emite por el DocumentDispatcher una factura construida por el motor tributario', function (): void {
    config()->set('quipu.pro', true);
    new FakeQuipu()->bind();

    $invoice = Quipu::invoice(ClientFactory::make())
        ->series('F001')
        ->number('1')
        ->cash()
        ->addLine('P001', 'Producto', 2.0, 50.0)
        ->build();

    $record = app(DocumentDispatcher::class)->dispatch($invoice);

    expect($record->state)->toBe(State::Accepted)
        ->and($record->series)->toBe('F001');
});
