<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Dispatching;

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\Quipu\Quipu;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Events\DocumentAccepted;
use ElPandaPe\QuipuLaravel\Events\DocumentRejected;
use ElPandaPe\QuipuLaravel\Events\DocumentVoided;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Models\Ticket;
use ElPandaPe\QuipuLaravel\Storage\DocumentStorage;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Resolves an asynchronous SUNAT ticket (daily summary / comunicación de baja):
 * polls its status, stores the CDR, records the ticket outcome and moves every
 * document it covers to its resolved state (Accepted/Observed, Voided for a baja,
 * or Rejected), firing the matching events.
 */
final readonly class TicketPoller
{
    public function __construct(
        private Quipu $quipu,
        private DocumentStorage $storage,
        private Dispatcher $events,
        private QuipuLogger $logger,
        private RejectionDiagnoser $diagnoser,
    ) {}

    /**
     * Poll a ticket once. Returns true when SUNAT has resolved it (ticket and
     * covered documents updated), false when it is still being processed or the
     * query failed — so the caller can retry later.
     */
    public function poll(Ticket $ticket): bool
    {
        try {
            $cdr = $this->quipu->getStatus($ticket->ticket);
        } catch (TransportException) {
            $this->logger->warning('El ticket sigue en proceso o la consulta falló.', [
                'ticket_id' => $ticket->id,
            ]);

            return false;
        }

        if ($cdr->xml !== null) {
            $this->storage->putCdr('R-' . $ticket->ticket . '.xml', $cdr->xml);
        }

        $ticket->state = $cdr->status->value;
        $ticket->save();

        foreach ($ticket->documents as $document) {
            $this->resolveDocument($document, $ticket, $cdr);
        }

        $this->logger->info('Ticket resuelto.', [
            'ticket_id' => $ticket->id,
            'status' => $cdr->status->value,
        ]);

        return true;
    }

    private function resolveDocument(Document $document, Ticket $ticket, CdrResult $cdr): void
    {
        if ($cdr->status === CdrStatus::Rejected) {
            // Mirror the synchronous path (DocumentDispatcher): attach the Pro
            // diagnosis to the async rejection so the event and the log carry the
            // actionable RejectionReport. It stays null on a base install.
            $diagnosis = $this->diagnoser->forCdr($cdr);
            if ($this->transition($document, State::Rejected, static fn(): DocumentRejected => new DocumentRejected($document, $cdr, $diagnosis))) {
                $this->logger->warning('SUNAT rechazó el comprobante.', $this->rejectionContext($document, $cdr->responseCode, $diagnosis));
            }

            return;
        }

        if ($ticket->document_type === DocumentType::VoidedDocuments) {
            $this->transition($document, State::Voided, static fn(): DocumentVoided => new DocumentVoided($document));

            return;
        }

        $target = $cdr->status === CdrStatus::AcceptedWithObservations ? State::Observed : State::Accepted;
        $this->transition($document, $target, static fn(): DocumentAccepted => new DocumentAccepted($document, $cdr));
    }

    /**
     * Move a covered document to $target and fire its event, skipping (with a
     * log line) any document whose current state does not allow the jump. Returns
     * true when the document transitioned, false when it was skipped.
     *
     * @param callable(): object $event
     */
    private function transition(Document $document, State $target, callable $event): bool
    {
        if (!$document->state->canTransitionTo($target)) {
            $this->logger->warning('El comprobante no admite la transición; se omite.', [
                'document_id' => $document->id,
                'from' => $document->state->value,
                'to' => $target->value,
            ]);

            return false;
        }

        $document->transitionTo($target);
        $this->events->dispatch($event());

        return true;
    }

    /**
     * Warning context for a rejected CDR, enriched with the Pro diagnosis when
     * available. Mirrors DocumentDispatcher's synchronous rejection context.
     *
     * @return array<string, scalar|null>
     */
    private function rejectionContext(Document $document, string $responseCode, ?RejectionReport $diagnosis): array
    {
        $context = [
            'document_id' => $document->id,
            'response_code' => $responseCode,
        ];

        if ($diagnosis instanceof RejectionReport) {
            $context['action'] = $diagnosis->action;
            $context['remedy'] = $diagnosis->remedy;
            $context['retryable'] = $diagnosis->retryable;
        }

        return $context;
    }
}
