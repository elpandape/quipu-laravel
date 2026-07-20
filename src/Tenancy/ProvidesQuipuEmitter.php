<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

/**
 * Contract a per-tenant model implements to expose its SUNAT emitter, so a
 * tenancy resolver can assemble an EmitterConfig — and pick the storage disk and
 * series prefix — for the active tenant without this package knowing the host
 * application's Tenant model.
 *
 * The {@see StanclTenantResolver} and {@see SpatieTenantResolver} consume this
 * contract to build the active emitter from the current tenant (including its
 * own decrypted certificate PEM). With config('quipu.tenancy.driver') = "none"
 * (the default) the package stays mono-tenant and this interface is unused; the
 * {@see \ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver} seam is the
 * extension point a tenancy driver plugs into.
 */
interface ProvidesQuipuEmitter
{
    /** RUC of the issuing taxpayer for this tenant. */
    public function quipuRuc(): string;

    /** Razón social (legal name) of the issuer. */
    public function quipuLegalName(): string;

    /** Optional commercial (trade) name of the issuer. */
    public function quipuTradeName(): ?string;

    /** SOL (Clave SOL) username for SUNAT's SOAP services. */
    public function quipuSolUser(): string;

    /** SOL (Clave SOL) password. */
    public function quipuSolPass(): string;

    /**
     * The signing certificate as an already-decrypted PEM (X.509 certificate plus
     * private key). The tenant model owns how it is stored and decrypted (e.g. a
     * Laravel "encrypted" column); this package never sees the .pfx/passphrase.
     */
    public function quipuCertificatePem(): string;

    /** Passphrase of the PEM private key, or null when it is not encrypted. */
    public function quipuCertificatePassphrase(): ?string;

    /**
     * IGV rate (percentage points, e.g. 8.0 for the MYPE regime) this tenant emits
     * at, or null to use the global config('quipu.igv_rate') default.
     */
    public function quipuIgvRate(): ?float;

    /** Series prefix reserved for this tenant (e.g. "F" for F001), or null for the default. */
    public function quipuSeriesPrefix(): ?string;

    /** Filesystem disk where this tenant's signed XML and CDR are stored, or null for the default. */
    public function quipuStorageDisk(): ?string;
}
