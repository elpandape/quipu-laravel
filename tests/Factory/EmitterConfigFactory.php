<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Factory;

use ElPandaPe\QuipuLaravel\Emitter\EmitterConfig;
use ElPandaPe\QuipuLaravel\Emitter\Environment;

/**
 * Builds EmitterConfig instances for the tests, with sane defaults so each test
 * only spells out the field it cares about.
 */
final class EmitterConfigFactory
{
    public static function make(
        ?string $certificatePassphrase = null,
        Environment $environment = Environment::Beta,
        ?string $billServiceEndpointOverride = null,
        bool $verifyTls = true,
        string $legalName = 'QUIPU TEST SAC',
        ?string $tradeName = 'QUIPU TEST',
        ?string $certificatePem = null,
    ): EmitterConfig {
        return new EmitterConfig(
            ruc: '20000000001',
            legalName: $legalName,
            tradeName: $tradeName,
            solUser: 'MODDATOS',
            solPass: 'moddatos',
            certificatePassphrase: $certificatePassphrase,
            environment: $environment,
            billServiceEndpointOverride: $billServiceEndpointOverride,
            verifyTls: $verifyTls,
            certificatePem: $certificatePem,
        );
    }
}
