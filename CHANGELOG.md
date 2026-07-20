# Changelog

Todos los cambios notables de **quipu-laravel** (`elpandape/quipu-laravel`) se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/),
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [Unreleased]

## [1.0.0] - 2026-07-19

**Versión inicial.** Al ser la **primera** publicación, este registro solo lista lo que la versión
**incluye** (`Added`): no hay `Changed` ni `Fixed`, porque no existe una versión previa respecto de la cual
cambiar o corregir. A partir de aquí, los cambios se agruparán por tipo.

La **integración con Laravel** del emisor de **[`quipu-lite`](https://github.com/elpandape/quipu-php-lite)**
(MIT): lo cablea en el contenedor, la configuración y las facades, y **auto-detecta** la edición comercial
**`quipu-pro`** para activar sus capacidades. Construida con **100 % de cobertura de líneas y tipos**, **PHPStan
nivel max + strict-rules**, y probada sobre `orchestra/testbench` en la matriz **PHP 8.4/8.5** (sobre Laravel 13).

### Added

#### Cableado y configuración
- **`QuipuServiceProvider`** (auto-discovery): compone el emisor de Lite desde `config/quipu.php` y lo vincula
  como singleton; publica la config (`--tag=quipu-config`) y las migraciones (`--tag=quipu-migrations`).
- **Facade `Quipu`** — proxy del emisor (`emit`, `emitInvoice`, `sign`, `validate`, `sendBill`…), con carga del
  certificado desde `path` \| `inline` (PEM base64) \| `disk` (S3), endpoints beta/producción y verificación TLS.

#### Persistencia y ciclo de vida
- **`DocumentDispatcher`** — emite con persistencia: modelo `Document` con máquina de estados (`State`:
  `Draft` → `Signed` → `Sent` → `Accepted` \| `Observed` \| `Rejected` \| `Voided`), XML firmado y CDR guardados
  en un disco de Laravel, más los modelos `Ticket` (asíncrono) e `IdempotencyKey` (Pro).
- **Series/correlativos** — `CorrelativoManager` asigna el correlativo con **bloqueo atómico** transaccional.

#### Colas, eventos, scheduling y logging
- **Jobs**: `SendDocumentJob` (envío asíncrono) y `PollTicketJob` (sondeo de tickets).
- **Eventos**: `DocumentIssued`, `DocumentAccepted`, `DocumentRejected`, `DocumentVoided`, `CdrReceived`.
- **Scheduling** opt-in (`schedule.enabled`): sondeo de tickets, resumen diario, re-encolado de pendientes y
  poda del inbox, cada tarea con su cron configurable.
- **Logging** por canal dedicado; nunca registra credenciales, certificados ni el XML del documento.

#### Comandos Artisan (Lite)
- `quipu:install`, `quipu:send`, `quipu:status`, `quipu:summary`, `quipu:read`, `quipu:cdr:fetch`,
  `quipu:doctor` y `quipu:prune`.

#### Multi-tenant
- Resolución del **emisor activo por tenant** vía `config('quipu.tenancy.driver')`: `none` (mono-tenant),
  `stancl` (`stancl/tenancy`), `spatie` (`spatie/laravel-multitenancy`), `auto`, o el FQCN de un
  `EmitterConfigResolver` propio.
- Interfaz **`ProvidesQuipuEmitter`** y trait **`HasQuipuEmitter`** (deriva el emisor de columnas `quipu_*`,
  con las credenciales cifradas vía el cast `encrypted`). Cada tenant firma con su **propio certificado**, y
  `Quipu::forTenant($key, $callback)` emite dentro del contexto de un tenant concreto.
- **Tasa de IGV por tenant** (`quipu_igv_rate` / `quipuIgvRate()`), con fallback al `config('quipu.igv_rate')`
  global (p. ej. el 8 % del régimen MYPE).

#### Auto-detección de la edición Pro
- `config('quipu.pro')` = `auto` \| `true` \| `false`. Con Pro activo, el emisor se compone vía
  `QuipuPro::for(...)`: **sender resiliente** (logging → retry → idempotencia **persistente** en Eloquent),
  **validadores extra** y **diagnóstico** de rechazos. Se registran los comandos Pro (`quipu:cert:inspect`,
  `quipu:cert:convert`, `quipu:cert:alert`, `quipu:diagnose`, `quipu:xml:inspect`, `quipu:xml:diff`,
  `quipu:pro:retry`), el scheduling suma el smart-retry y la alerta de expiración del certificado, y los
  **builders fluidos** (`Quipu::invoice`/`creditNote`/`debitNote`) quedan pre-sembrados con el emisor.

#### Testing
- **`Quipu::fake()`** — cambia el emisor por dobles en memoria (estilo `Mail::fake()`), sin red ni certificado,
  con aserciones `assertSent`/`assertSentCount`/`assertNothingSent` y respuestas configurables
  (`acceptsEverything`/`rejectsEverything`/`observesEverything`). Reutiliza el testing toolkit de Pro cuando la
  edición Pro está activa.

[Unreleased]: https://github.com/elpandape/quipu-laravel/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/elpandape/quipu-laravel/releases/tag/v1.0.0
