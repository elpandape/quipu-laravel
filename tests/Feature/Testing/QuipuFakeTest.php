<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu as QuipuEmitter;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\QuipuLaravel\Facades\Quipu;
use ElPandaPe\QuipuLaravel\Testing\QuipuFake;
use ElPandaPe\QuipuLaravel\Tests\Support\StubDocument;
use PHPUnit\Framework\AssertionFailedError;

dataset('editions', [
    'con Pro' => [true],
    'sin Pro' => [false],
]);

it('intercepta el envío y lo registra para assertSent', function (bool $pro): void {
    config()->set('quipu.pro', $pro);

    $fake = Quipu::fake();
    expect($fake)->toBeInstanceOf(QuipuFake::class);

    Quipu::emit(new StubDocument());

    Quipu::assertSent();
    Quipu::assertSentCount(1);
})->with('editions');

it('assertSent acepta un callback que inspecciona el XML firmado', function (bool $pro): void {
    config()->set('quipu.pro', $pro);
    Quipu::fake();

    Quipu::emit(new StubDocument());

    Quipu::assertSent(fn(SignedXml $signed): bool => $signed->digestValue === 'FAKE-DIGEST');
})->with('editions');

it('acceptsEverything devuelve un CDR aceptado', function (bool $pro): void {
    config()->set('quipu.pro', $pro);
    Quipu::fake()->acceptsEverything();

    $result = Quipu::emit(new StubDocument());

    expect($result->cdr->status)->toBe(CdrStatus::Accepted);
})->with('editions');

it('rejectsEverything devuelve un CDR rechazado con el código dado', function (bool $pro): void {
    config()->set('quipu.pro', $pro);
    Quipu::fake()->rejectsEverything('2335', 'Rechazado');

    $result = Quipu::emit(new StubDocument());

    expect($result->cdr->status)->toBe(CdrStatus::Rejected)
        ->and($result->cdr->responseCode)->toBe('2335');
})->with('editions');

it('observesEverything devuelve un CDR con observaciones', function (bool $pro): void {
    config()->set('quipu.pro', $pro);
    Quipu::fake()->observesEverything(['El dato no es válido.']);

    $result = Quipu::emit(new StubDocument());

    expect($result->cdr->status)->toBe(CdrStatus::AcceptedWithObservations)
        ->and($result->cdr->notes)->toBe(['El dato no es válido.']);
})->with('editions');

it('assertNothingSent pasa cuando no se emitió nada', function (bool $pro): void {
    config()->set('quipu.pro', $pro);
    Quipu::fake();

    Quipu::assertNothingSent();
})->with('editions');

it('assertSent falla cuando no se envió ningún comprobante', function (): void {
    Quipu::fake();

    expect(fn() => Quipu::assertSent())->toThrow(AssertionFailedError::class);
});

it('assertSent falla cuando ningún envío coincide con el callback', function (): void {
    Quipu::fake();
    Quipu::emit(new StubDocument());

    expect(fn() => Quipu::assertSent(fn(SignedXml $signed): bool => false))
        ->toThrow(AssertionFailedError::class);
});

it('assertNothingSent falla cuando sí se envió', function (): void {
    Quipu::fake();
    Quipu::emit(new StubDocument());

    expect(fn() => Quipu::assertNothingSent())->toThrow(AssertionFailedError::class);
});

it('assertSentCount falla ante un conteo distinto', function (): void {
    Quipu::fake();
    Quipu::emit(new StubDocument());

    expect(fn() => Quipu::assertSentCount(2))->toThrow(AssertionFailedError::class);
});

it('las aserciones sin llamar a fake() lanzan un error claro', function (): void {
    expect(fn() => Quipu::assertNothingSent())->toThrow(RuntimeException::class);
});

it('el doble interno cubre resumen, pack, estado y guía sin tocar la red', function (): void {
    config()->set('quipu.pro', false);
    Quipu::fake();
    $emitter = app(QuipuEmitter::class);

    $signed = new SignedXml('<x/>', 'D');

    $summaryTicket = $emitter->sendSummary($signed);
    $packTicket = $emitter->sendPack([$signed], 'batch');
    $status = $emitter->getStatus('TICKET');
    $packStatus = $emitter->getPackStatus('TICKET');
    $guideTicket = $emitter->emitGuide(new StubDocument());
    $guideStatus = $emitter->getGuideStatus('TICKET');

    expect($summaryTicket->ticket)->toBe('FAKE-TICKET')
        ->and($packTicket->ticket)->toBe('FAKE-TICKET')
        ->and($status->status)->toBe(CdrStatus::Accepted)
        ->and($packStatus)->toHaveKey('FAKE-FILE')
        ->and($guideTicket->ticket)->toBe('FAKE-GRE-TICKET')
        ->and($guideStatus->status)->toBe(CdrStatus::Accepted);
});
