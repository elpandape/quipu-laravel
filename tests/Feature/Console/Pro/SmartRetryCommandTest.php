<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Jobs\SendDocumentJob;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeQuipu;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('quipu.emisor.ruc', '20000000001');
    config()->set('quipu.emisor.sol_user', 'MODDATOS');
    config()->set('quipu.emisor.sol_pass', 'moddatos');
    config()->set('quipu.certificate.path', CertificateFile::plain());
    Storage::fake('local');
});

it('se niega a correr sin Pro', function (): void {
    config()->set('quipu.pro', false);

    expect(Artisan::call('quipu:pro:retry'))->toBe(1)
        ->and(Artisan::output())->toContain('requiere la edición Pro');
});

it('no hace nada cuando no hay pendientes', function (): void {
    config()->set('quipu.pro', true);
    new FakeQuipu()->bind();

    expect(Artisan::call('quipu:pro:retry'))->toBe(0)
        ->and(Artisan::output())->toContain('No hay comprobantes pendientes');
});

it('reenvía y resuelve un pendiente cuando SUNAT acepta', function (): void {
    config()->set('quipu.pro', true);
    new FakeQuipu()->bind();
    $document = app(DocumentDispatcher::class)->issue(new StubDocument());

    expect(Artisan::call('quipu:pro:retry', ['--id' => (string) $document->id]))->toBe(0)
        ->and(Artisan::output())->toContain('accepted');
});

it('reencola una excepción transitoria (reintentable)', function (): void {
    config()->set('quipu.pro', true);
    $fake = new FakeQuipu()->bind();
    $document = app(DocumentDispatcher::class)->issue(new StubDocument());
    $fake->sender->sendBillError = new SunatFaultException('102', 'credenciales');
    Queue::fake();

    expect(Artisan::call('quipu:pro:retry', ['--id' => (string) $document->id]))->toBe(0)
        ->and(Artisan::output())->toContain('reencolado');
    Queue::assertPushed(SendDocumentJob::class);
});

it('no reencola un rechazo que requiere corrección (no reintentable)', function (): void {
    config()->set('quipu.pro', true);
    $fake = new FakeQuipu()->bind();
    $document = app(DocumentDispatcher::class)->issue(new StubDocument());
    $fake->sender->sendBillError = new SunatFaultException('1033', 'numeración ya registrada');
    Queue::fake();

    expect(Artisan::call('quipu:pro:retry', ['--id' => (string) $document->id]))->toBe(0)
        ->and(Artisan::output())->toContain('no reintentable');
    Queue::assertNotPushed(SendDocumentJob::class);
});

it('reencola un fallo de transporte transitorio', function (): void {
    config()->set('quipu.pro', true);
    $fake = new FakeQuipu()->bind();
    $document = app(DocumentDispatcher::class)->issue(new StubDocument());
    $fake->sender->sendBillError = new TransportException('red caída');
    Queue::fake();

    expect(Artisan::call('quipu:pro:retry', ['--id' => (string) $document->id]))->toBe(0)
        ->and(Artisan::output())->toContain('reencolado');
    Queue::assertPushed(SendDocumentJob::class);
});
