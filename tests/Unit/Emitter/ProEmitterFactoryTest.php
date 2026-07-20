<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\PathCertificateResolver;
use ElPandaPe\QuipuLaravel\Emitter\ProEmitterFactory;
use ElPandaPe\QuipuLaravel\Tests\Factory\EmitterConfigFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\CertificateFile;
use ElPandaPe\QuipuPro\Idempotency\InMemoryResultStore;
use ElPandaPe\QuipuPro\Logging\OperationLogger;
use ElPandaPe\QuipuPro\Retry\RetryPolicy;

it('compone un emisor Pro (Quipu resiliente) desde el certificado y la config', function (): void {
    $factory = new ProEmitterFactory(
        new PathCertificateResolver(CertificateFile::plain()),
        new InMemoryResultStore(),
        new OperationLogger(),
        RetryPolicy::default(),
    );

    expect($factory->make(EmitterConfigFactory::make()))->toBeInstanceOf(Quipu::class);
});
