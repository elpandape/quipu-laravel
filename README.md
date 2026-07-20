# quipu-laravel

Integración de [quipu](https://quipu.elpanda.pe) con Laravel: la maquinaria de
**facturación electrónica de SUNAT** (CPE Perú) —construir el XML UBL 2.1, firmarlo, enviarlo a los
webservices SOAP de SUNAT y parsear el CDR— cableada en el contenedor, la configuración y las facades de
Laravel. Detecta automáticamente la edición **Pro** (`elpandape/quipu-pro`) y activa sus capacidades cuando
está instalada.

- 🟢 **Lite** (base): emisión, persistencia, series/correlativos, máquina de estados, almacenamiento por
  disco, jobs, eventos, scheduling y comandos Artisan.
- 🔵 **Pro** (auto-detectado): envío resiliente (reintentos + idempotencia), validadores extra, diagnóstico
  de rechazos, motor tributario con builders fluidos y certificados `.pfx`.

## Requisitos

- PHP **8.4+**
- Laravel **12 o 13** (la edición Pro requiere Laravel **13+**, por `brick/money`)

## Instalación

```bash
composer require elpandape/quipu-laravel
```

El `ServiceProvider` se registra por auto-discovery. Publica la configuración (opcional) con:

```bash
php artisan vendor:publish --tag=quipu-config
```

Las migraciones (comprobantes, tickets, series, idempotencia) se cargan solas; para adueñarte del esquema:

```bash
php artisan vendor:publish --tag=quipu-migrations
php artisan migrate
```

## Instalar la edición Pro

`elpandape/quipu-pro` es **comercial** y **no está en Packagist**: se sirve desde el registro privado
`https://packages.elpanda.pe`, con las credenciales de tu licencia (usuario = el correo de la licencia,
contraseña = el token). Regístralo en tu proyecto:

```bash
composer config repositories.quipu composer https://packages.elpanda.pe
composer config http-basic.packages.elpanda.pe <correo> <token>
composer require elpandape/quipu-pro
```

Una vez instalado, quipu-laravel lo detecta solo (`config('quipu.pro')` = `auto`) y activa sus capacidades.
Las credenciales quedan en `auth.json`: no lo subas al repositorio.

## Configuración mínima

Todo se lee de `config/quipu.php`, respaldado por variables de entorno. Lo mínimo en tu `.env`:

```dotenv
# Emisor (contribuyente)
QUIPU_RUC=20123456789
QUIPU_LEGAL_NAME="ACME CORP SAC"
QUIPU_SOL_USER=MODDATOS
QUIPU_SOL_PASS=moddatos

# Ambiente SUNAT: "beta" (homologación) o "produccion"
QUIPU_ENVIRONMENT=beta

# Certificado de firma (PEM: certificado X.509 + clave privada)
QUIPU_CERT_SOURCE=path        # path | inline | disk
QUIPU_CERTIFICATE_PATH=/ruta/al/certificado.pem
QUIPU_CERTIFICATE_PASSPHRASE=
```

**Fuente del certificado** (`QUIPU_CERT_SOURCE`) — pensada para entornos cloud, donde una ruta local no
persiste:

| Fuente   | Variable                | Uso                                                        |
|----------|-------------------------|------------------------------------------------------------|
| `path`   | `QUIPU_CERTIFICATE_PATH`| Archivo PEM local (solo desarrollo).                       |
| `inline` | `QUIPU_CERT_PEM`        | El PEM completo, codificado en base64 (mono-tenant cloud). |
| `disk`   | `QUIPU_CERT_DISK`       | Un disco de `config/filesystems.php` (p. ej. S3).          |

El almacenamiento de XML firmado y CDR usa un disco de Laravel (`QUIPU_STORAGE_DISK`, por defecto `local`);
cualquier disco —incluido `s3`— funciona sin código especial.

La **tasa de IGV** que los builders fluidos de Pro aplican a cada línea gravada se toma de `QUIPU_IGV_RATE`
(config `igv_rate`, por defecto `18.0`). Cámbiala ante una variación nacional o para el **8 % del régimen
MYPE** (restaurantes/hoteles, Ley 31556); puede sobrescribirse por documento, por línea y por tenant.

## Uso

### Emitir un comprobante

```php
use ElPandaPe\QuipuLaravel\Facades\Quipu;

$result = Quipu::emit($invoice);   // construye, firma y reporta a SUNAT

if ($result->cdr->isAccepted()) {
    // aceptado
}
```

Con persistencia y máquina de estados (fila `Document`, XML y CDR almacenados, eventos disparados), usa el
`DocumentDispatcher`:

```php
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;

$record = app(DocumentDispatcher::class)->dispatch($invoice);
// $record->state === State::Accepted | Observed | Rejected
```

### Builders fluidos (Pro)

Con la edición Pro instalada, el facade expone builders con el motor tributario ya sembrado con tu emisor
(calcula IGV/ISC/etc.):

```php
$invoice = Quipu::invoice($client)
    ->addLine('P001', 'Servicio de consultoría', quantity: 1, unitValue: 1000.00)
    ->build();

Quipu::emit($invoice);
```

También `Quipu::creditNote($client, $reason)` y `Quipu::debitNote($client, $reason)`. Sin Pro, estos métodos
lanzan una excepción clara.

### Comandos Artisan

🟢 **Lite** (siempre disponibles):

| Comando            | Qué hace                                             |
|--------------------|------------------------------------------------------|
| `quipu:install`    | Publica la configuración y las migraciones.          |
| `quipu:send`       | Firma y envía un comprobante.                         |
| `quipu:status`     | Consulta un ticket (o sondea los pendientes).         |
| `quipu:summary`    | Envía un resumen diario.                              |
| `quipu:read`       | Lee un XML de vuelta a su modelo tipado.             |
| `quipu:cdr:fetch`  | Re-descarga el CDR de un comprobante ya declarado.   |
| `quipu:doctor`     | Diagnóstico de la configuración.                     |
| `quipu:prune`      | Poda el almacenamiento (inbox).                      |

🔵 **Pro** (se registran solo con la edición Pro): `quipu:cert:inspect`, `quipu:cert:convert` (`.pfx`→PEM),
`quipu:cert:alert`, `quipu:diagnose`, `quipu:xml:inspect`, `quipu:xml:diff`, `quipu:pro:retry`. Además,
`quipu:doctor` se enriquece con el pre-flight del certificado.

Los comandos que tocan archivos aceptan `--disk=` y `--path=` para apuntar a otro disco (incl. S3). El
archivo se indica como argumento posicional en `quipu:read <archivo>`, `quipu:cert:inspect [<archivo>]`,
`quipu:cert:convert <pfx>`, `quipu:xml:inspect <archivo> <xpath>` y `quipu:xml:diff <a> <b>`; y como
`--file=` en `quipu:summary` (el XML a enviar) y `quipu:cdr:fetch` (el nombre con que se guarda el CDR,
cuyo argumento posicional es el id del comprobante).

### Colas, eventos y scheduling

- **Jobs** (`config('quipu.queue.connection')`): envío asíncrono (`SendDocumentJob`) y sondeo de tickets
  (`PollTicketJob`).
- **Eventos**: `DocumentIssued`, `DocumentAccepted`, `DocumentRejected`, `DocumentVoided`, `CdrReceived`.
  Registra listeners para reaccionar a cada resultado de SUNAT.
- **Scheduling** (`config('quipu.schedule.enabled')`, off por defecto): sondeo de tickets, resumen diario,
  re-encolado de pendientes y poda. Con Pro se añade el reintento inteligente y la alerta de expiración del
  certificado.
- **Logging**: canal dedicado configurable (`config('quipu.logging.channel')`); nunca se registran
  credenciales, certificados ni el XML del documento.

## Auto-detección de la edición Pro

`config('quipu.pro')` controla las capacidades Pro:

- `"auto"` (por defecto) — se activan si `elpandape/quipu-pro` está instalado; si no, degrada limpio a Lite.
- `true` — fuerza Pro; si el paquete falta, lanza un error claro en vez de degradar.
- `false` — fuerza Lite.

Con Pro activo, el emisor se compone vía `QuipuPro::for(...)`: sender resiliente (logging → retry →
idempotencia), validadores extra, idempotencia persistente (Eloquent) y diagnóstico de rechazos.

## Multi-tenant

El paquete resuelve el **emisor activo por tenant**: cada tenant emite con su **propio RUC, credenciales SOL
y certificado**. El comportamiento se elige con `config('quipu.tenancy.driver')`:

| Driver           | Resuelve el emisor desde…                                          |
|------------------|--------------------------------------------------------------------|
| `none` (default) | Mono-tenant: el `emisor` de `config/quipu.php`.                     |
| `stancl`         | El tenant actual de `stancl/tenancy`.                              |
| `spatie`         | El tenant actual de `spatie/laravel-multitenancy`.                 |
| `auto`           | El de esos dos paquetes que esté instalado.                        |
| `<FQCN>`         | Una clase `EmitterConfigResolver` propia, resuelta del contenedor. |

Con `stancl`/`spatie`/`auto`, tu modelo Tenant expone su emisor implementando la interfaz
`Tenancy\ProvidesQuipuEmitter`. Lo más simple es usar el trait `Tenancy\HasQuipuEmitter`, que la deriva de
columnas convencionales del modelo:

```php
use ElPandaPe\QuipuLaravel\Tenancy\HasQuipuEmitter;
use ElPandaPe\QuipuLaravel\Tenancy\ProvidesQuipuEmitter;

class Tenant extends BaseTenant implements ProvidesQuipuEmitter
{
    use HasQuipuEmitter;   // deriva el emisor de las columnas quipu_* del tenant
}
```

Columnas por defecto (cada una sobrescribible redeclarando su método `*Column()`): `quipu_ruc`,
`quipu_legal_name`, `quipu_trade_name`, `quipu_sol_user`, `quipu_sol_pass`, `quipu_certificate` (el PEM del
tenant), `quipu_certificate_passphrase`, `quipu_igv_rate`, `quipu_series_prefix` y `quipu_disk`. Las tres
credenciales (`quipu_sol_pass`, `quipu_certificate`, `quipu_certificate_passphrase`) reciben el cast
`encrypted` de Laravel automáticamente: se cifran en la base con tu `APP_KEY` y se descifran al leerlas —el
paquete nunca ve el `.pfx`—.

Normalmente el tenant ya está activo (lo puso el middleware de tu paquete de tenancy) y `Quipu::emit()` firma
con su certificado sin más. Para emitir dentro del contexto de un tenant concreto **fuera** de ese flujo,
envuélvelo con `Quipu::forTenant`:

```php
use ElPandaPe\QuipuLaravel\Facades\Quipu;

Quipu::forTenant($tenantKey, fn () => Quipu::emit($invoice));
```

`forTenant` activa el tenant vía el driver configurado, refresca el emisor (para que firme con el certificado
de ese tenant) y restaura el tenant previo al terminar. Con driver `none` lanza una
`TenancyNotImplementedException` clara —igual que un driver desconocido, o `auto` sin ninguno de los dos
paquetes de tenancy instalados—.

> La tasa de IGV por tenant sale de `quipu_igv_rate` (o `ProvidesQuipuEmitter::quipuIgvRate()`); si es `null`
> se usa el `config('quipu.igv_rate')` global. La fuente de certificado global `source => 'database'` (un
> almacén central) sigue pendiente y es **otra cosa**: los certificados **por tenant** ya funcionan vía el
> modelo Tenant.

## Testing

Para que tu app pruebe su integración con SUNAT **sin red ni certificado**, `Quipu::fake()` cambia el emisor
por dobles en memoria —al estilo de `Mail::fake()`— y expone aserciones:

```php
use ElPandaPe\QuipuLaravel\Facades\Quipu;

Quipu::fake();

// ... el código de tu app emite un comprobante ...
Quipu::emit($invoice);

Quipu::assertSent();                 // se envió al menos un comprobante
Quipu::assertSentCount(1);           // exactamente uno
Quipu::assertSent(fn($signed) => str_contains($signed->xml, 'F001'));
Quipu::assertNothingSent();          // no se envió nada
```

Controla la respuesta simulada de SUNAT desde el handle que devuelve `fake()`:

```php
Quipu::fake()->rejectsEverything('2335');       // todo se rechaza
Quipu::fake()->observesEverything(['...']);      // aceptado con observaciones
Quipu::fake()->acceptsEverything();              // aceptado (por defecto)
```

Con la edición Pro instalada, `Quipu::fake()` **reutiliza el testing toolkit de Pro** (`FakeSender`,
`PayloadRecorder`, …). Para afirmar sobre el CDR devuelto puedes usar el `CdrAsserter` de Pro:

```php
use ElPandaPe\QuipuPro\Testing\CdrAsserter;

CdrAsserter::for(Quipu::emit($invoice))->isAccepted();
```

## 📚 Documentación

[Documentación](https://quipu.elpanda.pe/integraciones/laravel)

## Contribuir

Las contribuciones son bienvenidas —reportes de bugs, rechazos de SUNAT con evidencia, tests y documentación—.
Lee la [guía de contribución](./CONTRIBUTING.md).

## Seguridad

Si descubres una vulnerabilidad de seguridad, **no** abras un issue público: escribe a
**contacto@elpanda.pe**.

## Licencia

Distribuido bajo licencia **MIT** — ver [LICENSE.md](./LICENSE.md).

Paquete **no oficial**: no está afiliado, avalado ni patrocinado por SUNAT.
