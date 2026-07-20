<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\Environment;
use ElPandaPe\QuipuLaravel\Tests\Factory\EmitterConfigFactory;

it('concatena el RUC con el usuario SOL para el username SOAP', function (): void {
    expect(EmitterConfigFactory::make()->soapUsername())->toBe('20000000001MODDATOS');
});

it('usa el endpoint del entorno cuando no hay override', function (): void {
    $config = EmitterConfigFactory::make(environment: Environment::Production);

    expect($config->billServiceEndpoint())
        ->toBe('https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService');
});

it('prioriza el endpoint override cuando está configurado', function (): void {
    $endpoint = 'https://mi-proxy.example/billService';

    $config = EmitterConfigFactory::make(billServiceEndpointOverride: $endpoint);

    expect($config->billServiceEndpoint())->toBe($endpoint);
});
