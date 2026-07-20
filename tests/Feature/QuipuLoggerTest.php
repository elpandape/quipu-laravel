<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Tests\Support\RecordingLogger;

it('decora el contexto con el componente y delega en cada nivel', function (): void {
    $inner = new RecordingLogger();
    $logger = new QuipuLogger($inner);

    $logger->info('firmado', ['document_id' => 7]);
    $logger->warning('rechazado');
    $logger->error('caído', ['ticket_id' => 3]);

    expect($inner->records)->toBe([
        ['level' => 'info', 'message' => 'firmado', 'context' => ['component' => 'quipu', 'document_id' => 7]],
        ['level' => 'warning', 'message' => 'rechazado', 'context' => ['component' => 'quipu']],
        ['level' => 'error', 'message' => 'caído', 'context' => ['component' => 'quipu', 'ticket_id' => 3]],
    ]);
});

it('resuelve un canal de log dedicado cuando está configurado', function (): void {
    config()->set('logging.channels.pruebas', ['driver' => 'null']);
    config()->set('quipu.logging.channel', 'pruebas');

    expect(app(QuipuLogger::class))->toBeInstanceOf(QuipuLogger::class);
});

it('usa el canal por defecto cuando no se configura ninguno', function (): void {
    expect(app(QuipuLogger::class))->toBeInstanceOf(QuipuLogger::class);
});
