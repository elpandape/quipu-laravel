<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Dispatching;

use Carbon\CarbonImmutable;
use ElPandaPe\Quipu\Contract\Document as SaleDocument;
use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Quipu;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Events\CdrReceived;
use ElPandaPe\QuipuLaravel\Events\DocumentAccepted;
use ElPandaPe\QuipuLaravel\Events\DocumentIssued;
use ElPandaPe\QuipuLaravel\Events\DocumentRejected;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Storage\DocumentStorage;
use ElPandaPe\QuipuLaravel\Tenancy\TenantContext;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Ties the pieces of an emission together: sign a domain document with the Lite
 * emitter, persist it as a Document row, store its signed XML and CDR through the
 * configured disk, drive the lifecycle state machine and fire the domain events.
 *
 * A SUNAT rejection is recorded as the Rejected state (with DocumentRejected),
 * never thrown — only transport failures bubble up so the queue can retry.
 */
final readonly class DocumentDispatcher
{
    public function __construct(
        private Quipu $quipu,
        private DocumentStorage $storage,
        private Dispatcher $events,
        private QuipuLogger $logger,
        private RejectionDiagnoser $diagnoser,
        private TenantContext $tenants,
    ) {}

    /**
     * Sign and persist a domain document, storing its signed XML and leaving it
     * in the Signed state, ready to be reported to SUNAT. Fires DocumentIssued.
     */
    public function issue(SaleDocument $document, ?string $tenantId = null): Document
    {
        // Default to the active tenant so a multi-tenant install scopes the row
        // without every call site threading the id; an explicit id still wins.
        $tenantId ??= $this->tenants->currentTenantKey();

        $signed = $this->quipu->sign($document);
        $fileName = $document->fileName();
        $signedPath = $this->storage->putSignedXml($fileName . '.xml', $signed->xml);

        [$series, $number] = $this->identity($fileName);

        $record = Document::query()->create([
            'tenant_id' => $tenantId,
            'document_type' => $document->documentType(),
            'series' => $series,
            'number' => $number,
            'issued_at' => CarbonImmutable::now(),
            'signed_xml_path' => $signedPath,
            'digest' => $signed->digestValue,
        ]);

        $record->transitionTo(State::Signed);

        $this->logger->info('Comprobante firmado y almacenado.', [
            'document_id' => $record->id,
            'file' => $fileName,
        ]);

        $this->events->dispatch(new DocumentIssued($record));

        return $record;
    }

    /**
     * Report an already-issued (Signed) document to SUNAT and record the outcome:
     * store the CDR, move to Accepted/Observed/Rejected and fire the events.
     */
    public function send(Document $record): Document
    {
        $signedXml = new SignedXml(
            $this->storage->getSignedXml((string) $record->signed_xml_path),
            (string) $record->digest,
        );

        try {
            $result = $this->quipu->sendBill($signedXml);
        } catch (SunatFaultException $fault) {
            $this->logFault($record, $fault);

            throw $fault;
        }

        $record->transitionTo(State::Sent);

        return $this->recordCdr($record, $result->cdr);
    }

    /** Issue and report a document in one step, returning the resolved record. */
    public function dispatch(SaleDocument $document, ?string $tenantId = null): Document
    {
        return $this->send($this->issue($document, $tenantId));
    }

    private function recordCdr(Document $record, CdrResult $cdr): Document
    {
        if ($cdr->xml !== null) {
            $record->cdr_path = $this->storage->putCdr($this->cdrFileName($record), $cdr->xml);
        }

        $record->sunat_status = $cdr->status->value;
        $record->sunat_response_code = $cdr->responseCode;
        $record->save();

        $this->events->dispatch(new CdrReceived($record, $cdr));

        $record->transitionTo($this->stateFor($cdr->status));

        if ($cdr->status === CdrStatus::Rejected) {
            $diagnosis = $this->diagnoser->forCdr($cdr);
            $this->logger->warning('SUNAT rechazó el comprobante.', $this->rejectionContext($record, $cdr->responseCode, $diagnosis));
            $this->events->dispatch(new DocumentRejected($record, $cdr, $diagnosis));

            return $record;
        }

        $this->logger->info('SUNAT aceptó el comprobante.', [
            'document_id' => $record->id,
            'response_code' => $cdr->responseCode,
        ]);
        $this->events->dispatch(new DocumentAccepted($record, $cdr));

        return $record;
    }

    /**
     * Log a synchronous SUNAT fault, enriched with the Pro diagnosis when
     * available. The fault still propagates (the caller/queue decides what to
     * do); this only records an actionable trace when Pro is active.
     */
    private function logFault(Document $record, SunatFaultException $fault): void
    {
        $diagnosis = $this->diagnoser->forFault($fault);
        if (!$diagnosis instanceof RejectionReport) {
            return;
        }

        $this->logger->warning('SUNAT rechazó el envío con un fault.', [
            'document_id' => $record->id,
            'fault_code' => $fault->faultCode,
            'action' => $diagnosis->action,
            'remedy' => $diagnosis->remedy,
            'retryable' => $diagnosis->retryable,
        ]);
    }

    /**
     * Warning context for a rejected CDR, enriched with the Pro diagnosis when
     * available.
     *
     * @return array<string, scalar|null>
     */
    private function rejectionContext(Document $record, string $responseCode, ?RejectionReport $diagnosis): array
    {
        $context = [
            'document_id' => $record->id,
            'response_code' => $responseCode,
        ];

        if ($diagnosis instanceof RejectionReport) {
            $context['action'] = $diagnosis->action;
            $context['remedy'] = $diagnosis->remedy;
            $context['retryable'] = $diagnosis->retryable;
        }

        return $context;
    }

    private function stateFor(CdrStatus $status): State
    {
        return match ($status) {
            CdrStatus::Accepted => State::Accepted,
            CdrStatus::AcceptedWithObservations => State::Observed,
            CdrStatus::Rejected => State::Rejected,
        };
    }

    private function cdrFileName(Document $record): string
    {
        $base = pathinfo((string) $record->signed_xml_path, PATHINFO_FILENAME);

        return 'R-' . $base . '.xml';
    }

    /**
     * Split a SUNAT file name (…-SERIE-NUMERO) into its series and correlativo.
     *
     * @return array{string, int}
     */
    private function identity(string $fileName): array
    {
        $parts = explode('-', $fileName);
        $number = (int) array_pop($parts);
        $series = array_pop($parts) ?? '';

        return [$series, $number];
    }
}
