<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;

/**
 * Builds a Lite Quipu emitter from a resolved EmitterConfig by wiring the
 * shared EmitterComponents (builder, signer, sender, billConsult client) into a
 * plain Quipu — no resilience, no Pro validators. Used when the Pro edition is
 * inactive; the Pro path goes through ProEmitterFactory instead.
 */
final readonly class EmitterFactory
{
    public function __construct(private CertificateResolver $certificateResolver) {}

    public function make(EmitterConfig $config): Quipu
    {
        $components = new EmitterComponentsFactory($this->certificateResolver)->make($config);

        return new Quipu(
            $components->builder,
            $components->signer,
            $components->sender,
            cpeStatusService: $components->cpeStatusService,
        );
    }
}
