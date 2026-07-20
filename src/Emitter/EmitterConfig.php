<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

/**
 * Resolved configuration for the active emitter — everything EmitterFactory
 * needs to build a Lite Quipu instance. Produced by an EmitterConfigResolver so
 * a multi-tenant resolver can supply a tenant-scoped one without changing the
 * factory.
 */
final readonly class EmitterConfig
{
    /**
     * @param ?string $certificatePem The tenant's already-decrypted certificate
     *                                PEM, supplied by a multi-tenant resolver. When
     *                                null (the mono-tenant path) the emitter loads
     *                                the certificate from the configured
     *                                CertificateResolver source instead.
     */
    public function __construct(
        public string $ruc,
        public string $legalName,
        public ?string $tradeName,
        public string $solUser,
        public string $solPass,
        public ?string $certificatePassphrase,
        public Environment $environment,
        public ?string $billServiceEndpointOverride,
        public bool $verifyTls,
        public ?string $certificatePem = null,
        /**
         * Default IGV rate (percentage points) Pro's fluent builders apply to every
         * taxed line. Global by default; a multi-tenant resolver supplies the
         * tenant's own rate (e.g. the 8% MYPE regime) here.
         */
        public float $igvRate = 18.0,
    ) {}

    /** SOAP username SUNAT expects: the RUC concatenated with the SOL user. */
    public function soapUsername(): string
    {
        return $this->ruc . $this->solUser;
    }

    /** billService URL to send to: the override when set, otherwise the environment default. */
    public function billServiceEndpoint(): string
    {
        return $this->billServiceEndpointOverride ?? $this->environment->endpoints()->billServiceUrl();
    }

    /** billConsultService URL used to query a comprobante's status and re-download its CDR. */
    public function consultServiceEndpoint(): string
    {
        return $this->environment->endpoints()->consultUrl();
    }
}
