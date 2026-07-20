<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\Quipu\Model\Company;
use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuPro\Idempotency\ResultStore;
use ElPandaPe\QuipuPro\Logging\OperationLogger;
use ElPandaPe\QuipuPro\QuipuPro;
use ElPandaPe\QuipuPro\Retry\RetryPolicy;

/**
 * Assembles the Pro-composed Quipu emitter: the same Lite EmitterComponents
 * (builder, signer, SOAP sender, billConsult client) wired through
 * QuipuPro::for, which layers the resilient sender (logging → retry →
 * idempotency, in that order) and the base + Pro validators on top, then exposes
 * the fully-wired Lite Quipu via core(). The idempotency store is injected (the
 * persistent DatabaseResultStore in the container) so an accepted result
 * survives queue retries and redeploys.
 *
 * Only instantiated when the Pro edition is active, so referencing the QuipuPro
 * classes here is safe even on a Lite-only install.
 */
final readonly class ProEmitterFactory
{
    public function __construct(
        private CertificateResolver $certificateResolver,
        private ResultStore $resultStore,
        private OperationLogger $logger,
        private RetryPolicy $retryPolicy,
    ) {}

    /** The fully Pro-wired Lite emitter (QuipuPro's core) — the emission facade. */
    public function make(EmitterConfig $config): Quipu
    {
        return $this->makePro($config)->core();
    }

    /**
     * The full QuipuPro capstone: the resilient emitter plus the issuer-seeded
     * fluent builders and the diagnosis helpers. Bound as a shared singleton so
     * the emitter (via {@see make()}) and the fluent builders resolve the same
     * instance.
     */
    public function makePro(EmitterConfig $config): QuipuPro
    {
        $components = new EmitterComponentsFactory($this->certificateResolver)->make($config);

        return QuipuPro::for(
            // The real issuer feeds Pro's fluent builders; its legal name comes
            // from config('quipu.emisor.legal_name') (falling back to the RUC).
            issuer: new Company($config->ruc, $config->legalName, $config->tradeName),
            builder: $components->builder,
            signer: $components->signer,
            sender: $components->sender,
            logger: $this->logger,
            retryPolicy: $this->retryPolicy,
            resultStore: $this->resultStore,
            cpeStatusService: $components->cpeStatusService,
        );
    }
}
