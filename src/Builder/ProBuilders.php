<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Builder;

use ElPandaPe\Quipu\Catalog\CreditNoteType;
use ElPandaPe\Quipu\Catalog\DebitNoteType;
use ElPandaPe\Quipu\Model\Client;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Pro\ProUnavailableException;
use ElPandaPe\QuipuPro\Builder\FluentInvoiceBuilder;
use ElPandaPe\QuipuPro\Builder\FluentNoteBuilder;
use ElPandaPe\QuipuPro\QuipuPro;
use Illuminate\Contracts\Container\Container;

/**
 * Facade-facing gateway to Pro's tax engine: hands back the fluent
 * invoice/credit-note/debit-note builders already seeded with the configured
 * issuer (through the shared {@see QuipuPro} instance), so the app only supplies
 * the minimum and Pro computes IGV/ISC/etc. The assembled Lite model is then
 * emitted through the F3 DocumentDispatcher.
 *
 * The Pro types are only touched once {@see pro()} has confirmed the edition is
 * active; a Lite-only install gets a clear {@see ProUnavailableException} instead
 * of a fatal autoload error.
 */
final readonly class ProBuilders
{
    public function __construct(
        private ProDetector $detector,
        private Container $container,
        private EmitterConfigResolver $emitterResolver,
    ) {}

    public function invoice(Client $client): FluentInvoiceBuilder
    {
        return $this->pro()->invoice($client)->withIgvRate($this->igvRate());
    }

    public function creditNote(Client $client, CreditNoteType $reason = CreditNoteType::OtherConcepts): FluentNoteBuilder
    {
        return $this->pro()->creditNote($client, $reason)->withIgvRate($this->igvRate());
    }

    public function debitNote(Client $client, DebitNoteType $reason = DebitNoteType::PenaltiesOrOtherCharges): FluentNoteBuilder
    {
        return $this->pro()->debitNote($client, $reason)->withIgvRate($this->igvRate());
    }

    /**
     * The IGV rate for the active emitter — the global config default, or the
     * tenant's own rate when multi-tenant. Seeds every builder so a rate change or
     * a reduced regime (8% MYPE) is config-driven, still overridable per document.
     */
    private function igvRate(): float
    {
        return $this->emitterResolver->resolve()->igvRate;
    }

    /** The shared, issuer-seeded QuipuPro — or a clear failure when Pro is inactive. */
    private function pro(): QuipuPro
    {
        if (!$this->detector->isActive()) {
            throw ProUnavailableException::forFeature('El motor tributario (los constructores fluidos Quipu::invoice/creditNote/debitNote)');
        }

        return $this->container->make(QuipuPro::class);
    }
}
