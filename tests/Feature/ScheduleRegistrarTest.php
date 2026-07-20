<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\ScheduledCommands;

it('no registra tareas cuando el scheduling está deshabilitado', function (): void {
    config()->set('quipu.schedule.enabled', false);

    expect(ScheduledCommands::quipu(ScheduledCommands::fresh()))->toBeEmpty();
});

it('registra las tareas base (sin Pro) cuando el scheduling está habilitado', function (): void {
    config()->set('quipu.pro', false);
    config()->set('quipu.schedule.enabled', true);
    config()->set('quipu.schedule.daily_summary_file');

    $commands = ScheduledCommands::quipu(ScheduledCommands::fresh());

    expect($commands)->toHaveCount(3)
        ->and(implode(' ', $commands))->toContain('quipu:status')
        ->and(implode(' ', $commands))->toContain('quipu:send')
        ->and(implode(' ', $commands))->toContain('quipu:prune')
        ->and(implode(' ', $commands))->not->toContain('quipu:pro:retry')
        ->and(implode(' ', $commands))->not->toContain('quipu:cert:alert');
});

it('registra también el resumen diario cuando hay archivo configurado', function (): void {
    config()->set('quipu.pro', false);
    config()->set('quipu.schedule.enabled', true);
    config()->set('quipu.schedule.daily_summary_file', 'rc-del-dia.xml');

    $commands = ScheduledCommands::quipu(ScheduledCommands::fresh());

    expect($commands)->toHaveCount(4)
        ->and(implode(' ', $commands))->toContain('quipu:summary');
});

it('con Pro cambia el reintento simple por el inteligente y añade la alerta de certificado', function (): void {
    config()->set('quipu.pro', true);
    config()->set('quipu.schedule.enabled', true);
    config()->set('quipu.schedule.daily_summary_file');

    $commands = ScheduledCommands::quipu(ScheduledCommands::fresh());
    $joined = implode(' ', $commands);

    expect($commands)->toHaveCount(4)
        ->and($joined)->toContain('quipu:status')
        ->and($joined)->toContain('quipu:pro:retry')
        ->and($joined)->toContain('quipu:prune')
        ->and($joined)->toContain('quipu:cert:alert')
        ->and($joined)->not->toContain('quipu:send');
});
