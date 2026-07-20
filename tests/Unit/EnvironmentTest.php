<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Emitter\Environment;

it('mapea beta a los endpoints de homologación de SUNAT', function (): void {
    expect(Environment::Beta->endpoints()->billServiceUrl())
        ->toBe('https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService');
});

it('mapea produccion a los endpoints de producción de SUNAT', function (): void {
    expect(Environment::Production->endpoints()->billServiceUrl())
        ->toBe('https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService');
});
