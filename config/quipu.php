<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Emisor (taxpayer)
    |--------------------------------------------------------------------------
    |
    | The single emitter this integration issues documents for. This is the
    | mono-tenant default (tenancy.driver = "none"); with a multi-tenant driver
    | the active emitter is resolved per tenant instead (see the "Multi-tenant"
    | block below) and this one stays as the fallback.
    |
    */
    'emisor' => [
        'ruc' => env('QUIPU_RUC', ''),
        // Razón social of the issuing taxpayer. Feeds the issuer Company (and so
        // Pro's fluent builders). Falls back to the RUC when left empty.
        'legal_name' => env('QUIPU_LEGAL_NAME', ''),
        // Optional commercial (trade) name of the issuer.
        'trade_name' => env('QUIPU_TRADE_NAME'),
        'sol_user' => env('QUIPU_SOL_USER', ''),
        'sol_pass' => env('QUIPU_SOL_PASS', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | IGV rate
    |--------------------------------------------------------------------------
    |
    | Default IGV rate (percentage points) Pro's fluent builders apply to every
    | taxed line. It is the SAME across factura/boleta/nota — the rate is a
    | property of the operation/business, not the document type. Set it for a
    | national rate change, or to the 8.0 MYPE rate (restaurantes/hoteles, Ley
    | 31556). Multi-tenant: a tenant may override it via
    | ProvidesQuipuEmitter::quipuIgvRate() (this is the fallback). It can still be
    | overridden per document (withIgvRate) or per line (addLine igvRate:) at
    | emission time.
    |
    */
    'igv_rate' => env('QUIPU_IGV_RATE', 18.0),

    /*
    |--------------------------------------------------------------------------
    | Multi-tenant
    |--------------------------------------------------------------------------
    |
    | How the active emitter is resolved:
    |   "none"    — mono-tenant (the default): the single "emisor" above is used.
    |   "stancl"  — resolve the emitter from stancl/tenancy's current tenant.
    |   "spatie"  — resolve it from spatie/laravel-multitenancy's current tenant.
    |   "auto"    — use whichever of those two packages is installed.
    |   <class>   — a custom EmitterConfigResolver class, resolved from the
    |               container.
    | For "stancl"/"spatie"/"auto" the current Tenant model must implement
    | Tenancy\ProvidesQuipuEmitter, exposing the RUC, razón social, SOL
    | credentials and the already-decrypted certificate PEM of that tenant; the
    | emitter then signs with the tenant's own certificate. An unrecognised
    | driver — or "auto" with neither package installed — throws a clear
    | TenancyNotImplementedException.
    |
    */
    'tenancy' => [
        'driver' => env('QUIPU_TENANCY_DRIVER', 'none'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    |
    | Names of the tables this integration owns. Override them to fit an
    | existing schema or to avoid a clash with the host application; the
    | migrations, the Eloquent models and the correlativo counter all read
    | these values, so a single change here keeps everything consistent.
    |
    */
    'tables' => [
        'documents' => env('QUIPU_TABLE_DOCUMENTS', 'quipu_documents'),
        'tickets' => env('QUIPU_TABLE_TICKETS', 'quipu_tickets'),
        'series' => env('QUIPU_TABLE_SERIES', 'quipu_series'),
        // Pro: idempotency keys (digest → cached SUNAT result). Only used when
        // the Pro edition is active; harmless to keep otherwise.
        'idempotency' => env('QUIPU_TABLE_IDEMPOTENCY', 'quipu_idempotency_keys'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate
    |--------------------------------------------------------------------------
    |
    | Where the signing certificate (PEM: X.509 certificate + private key) is
    | loaded from. A local path does not survive serverless / Laravel Cloud, so
    | "source" abstracts it:
    |   "path"   — a local file (dev only).
    |   "inline" — the whole PEM base64-encoded in an env var (mono-tenant cloud).
    |   "disk"   — a Laravel filesystem disk from config/filesystems.php (e.g. S3).
    | Set "passphrase" when the private key is encrypted; it is applied after the
    | PEM is loaded. Loading a .pfx / .p12 directly is a Pro capability of a
    | later phase, as is the "database" (multi-tenant) source.
    |
    */
    'certificate' => [
        'source' => env('QUIPU_CERT_SOURCE', 'path'),
        // "path" source: local PEM file. Also the object path inside the disk
        // for the "disk" source.
        'path' => env('QUIPU_CERTIFICATE_PATH'),
        // "inline" source: the whole PEM, base64-encoded.
        'inline' => env('QUIPU_CERT_PEM'),
        // "disk" source: the filesystem disk name from config/filesystems.php.
        'disk' => env('QUIPU_CERT_DISK'),
        'passphrase' => env('QUIPU_CERTIFICATE_PASSPHRASE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Which SUNAT environment to target: "beta" (homologación) or "produccion".
    |
    */
    'environment' => env('QUIPU_ENVIRONMENT', 'beta'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint overrides
    |--------------------------------------------------------------------------
    |
    | Optional overrides for the SUNAT SOAP endpoints. Leave null to use the
    | defaults for the selected environment. "bill_service" is the billService
    | URL used for factura/boleta/nota/resumen/baja.
    |
    */
    'endpoints' => [
        'bill_service' => env('QUIPU_ENDPOINT_BILL_SERVICE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TLS verification
    |--------------------------------------------------------------------------
    |
    | Verify SUNAT's TLS certificate. Keep true in production. Set false only
    | to opt out for SUNAT beta, whose certificate cannot always be validated.
    |
    */
    'verify_tls' => env('QUIPU_VERIFY_TLS', true),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Filesystem disk where signed XML and CDR are persisted. Any disk from the
    | app's config/filesystems.php works — "local", "s3", or a custom one; S3
    | needs no special code, it is just another disk. "paths" are the logical
    | folders inside that disk: signed XML we generate, CDR returned by SUNAT,
    | and an inbox for XML uploaded by hand or downloaded from the portal.
    |
    */
    'storage' => [
        'disk' => env('QUIPU_STORAGE_DISK', 'local'),
        'paths' => [
            'signed' => env('QUIPU_STORAGE_PATH_SIGNED', 'signed'),
            'cdr' => env('QUIPU_STORAGE_PATH_CDR', 'cdr'),
            'inbox' => env('QUIPU_STORAGE_PATH_INBOX', 'inbox'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Queue connection for the asynchronous send/poll jobs. Null uses the
    | application default connection.
    |
    */
    'queue' => [
        'connection' => env('QUIPU_QUEUE_CONNECTION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Dedicated log channel for quipu's send/poll activity. Null routes to the
    | application's default channel. Every entry carries a "component" => "quipu"
    | context; credentials, certificates and document XML are never logged.
    |
    */
    'logging' => [
        'channel' => env('QUIPU_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | Opt-in scheduled maintenance run from the package service provider: poll
    | pending tickets, retry documents still awaiting SUNAT, send a prepared
    | daily summary, and prune the inbox. Disabled by default; each task's cron
    | expression is configurable. When the Pro edition is active the plain retry
    | is replaced by the smart retry (quipu:pro:retry) and the certificate
    | expiration alert (quipu:cert:alert) is added.
    |
    */
    'schedule' => [
        'enabled' => env('QUIPU_SCHEDULE_ENABLED', false),
        'poll_tickets_cron' => env('QUIPU_SCHEDULE_POLL_TICKETS_CRON', '*/15 * * * *'),
        'retry_pending_cron' => env('QUIPU_SCHEDULE_RETRY_PENDING_CRON', '*/30 * * * *'),
        'daily_summary_cron' => env('QUIPU_SCHEDULE_DAILY_SUMMARY_CRON', '0 1 * * *'),
        // File (in the inbox) sent by the scheduled daily-summary task. Null
        // leaves that task unregistered — a summary must be prepared per day.
        'daily_summary_file' => env('QUIPU_SCHEDULE_DAILY_SUMMARY_FILE'),
        'prune_cron' => env('QUIPU_SCHEDULE_PRUNE_CRON', '0 3 * * *'),
        // Pro (registered only when the Pro edition is active): the certificate
        // expiration alert cron and how many days before expiry it warns.
        'cert_alert_cron' => env('QUIPU_SCHEDULE_CERT_ALERT_CRON', '0 8 * * *'),
        'cert_expiry_days' => env('QUIPU_SCHEDULE_CERT_EXPIRY_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pro edition
    |--------------------------------------------------------------------------
    |
    | Whether to activate the Pro capabilities: "auto" detects quipu-php-pro at
    | runtime, true forces it on (erroring clearly if the package is missing),
    | false forces it off. When active the emitter is composed through
    | QuipuPro::for — a resilient sender (logging → retry → idempotency), the
    | extra Pro validators, persistent idempotency and rejection diagnosis. The
    | base wiring works unchanged without Pro.
    |
    */
    'pro' => env('QUIPU_PRO', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Retry policy (Pro)
    |--------------------------------------------------------------------------
    |
    | Backoff for the Pro resilient sender: only SUNAT's transient system
    | exceptions (codes 100-999) are retried; format errors and rejections are
    | not. Deterministic exponential backoff (baseDelayMs * factor^(n-1),
    | capped at capDelayMs). Ignored when Pro is inactive.
    |
    */
    'retry' => [
        'max_attempts' => env('QUIPU_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => env('QUIPU_RETRY_BASE_DELAY_MS', 1000),
        'factor' => env('QUIPU_RETRY_FACTOR', 2.0),
        'cap_delay_ms' => env('QUIPU_RETRY_CAP_DELAY_MS', 30000),
    ],

];
