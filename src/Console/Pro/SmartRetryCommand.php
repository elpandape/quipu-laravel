<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\Quipu\Exception\SunatFaultException;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionDiagnoser;
use ElPandaPe\QuipuLaravel\Diagnosing\RejectionReport;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Jobs\SendDocumentJob;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Retry\RetryPolicy;
use Illuminate\Database\Eloquent\Collection;

/**
 * Smart retry of pending (signed but not accepted) documents: it attempts each
 * send and, on failure, classifies the error with Pro's {@see RetryPolicy}
 * (retrying only the transient system-exception band and transport failures) and
 * {@see RejectionDiagnoser}. Retryable failures are re-queued; the rest are left
 * for correction, logged with the actionable remedy — instead of F3's blind
 * re-queue of everything.
 */
final class SmartRetryCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:pro:retry
        {--id= : Reintentar solo el comprobante con este id}';

    /** @var string */
    protected $description = 'Reintenta los comprobantes pendientes, reencolando solo los reintentables (Pro).';

    public function handle(ProDetector $detector): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $pending = $this->pending();
        if ($pending->isEmpty()) {
            $this->info('No hay comprobantes pendientes de reintento.');

            return self::SUCCESS;
        }

        $dispatcher = app(DocumentDispatcher::class);
        $retryPolicy = app(RetryPolicy::class);
        $diagnoser = app(RejectionDiagnoser::class);
        $logger = app(QuipuLogger::class);

        foreach ($pending as $document) {
            $this->attempt($document, $dispatcher, $retryPolicy, $diagnoser, $logger);
        }

        return self::SUCCESS;
    }

    /** @return Collection<int, Document> */
    private function pending(): Collection
    {
        $query = Document::query()->where('state', State::Signed);

        $id = $this->optionString('id');
        if ($id !== null) {
            $query->whereKey((int) $id);
        }

        return $query->get();
    }

    private function attempt(
        Document $document,
        DocumentDispatcher $dispatcher,
        RetryPolicy $retryPolicy,
        RejectionDiagnoser $diagnoser,
        QuipuLogger $logger,
    ): void {
        try {
            $sent = $dispatcher->send($document);
            $this->line(sprintf('#%d: %s.', $sent->id, $sent->state->value));
        } catch (SunatFaultException $fault) {
            $this->onFault($document, $fault, $retryPolicy, $diagnoser, $logger);
        } catch (TransportException $transport) {
            // A plain transport failure is always transient — worth re-queuing.
            $this->requeue($document, $logger, 'fallo de transporte transitorio', $transport->getMessage());
        }
    }

    private function onFault(
        Document $document,
        SunatFaultException $fault,
        RetryPolicy $retryPolicy,
        RejectionDiagnoser $diagnoser,
        QuipuLogger $logger,
    ): void {
        $report = $diagnoser->forFault($fault);
        $action = $report instanceof RejectionReport ? $report->action : 'Corregir el comprobante';
        $remedy = $report instanceof RejectionReport ? $report->remedy : '';

        if ($retryPolicy->isRetryable($fault)) {
            $this->requeue($document, $logger, sprintf('excepción transitoria %s', $fault->faultCode), $remedy);

            return;
        }

        $this->warn(sprintf('#%d: no reintentable (%s) — %s.', $document->id, $fault->faultCode, $action));
        $logger->warning('Reintento inteligente: comprobante no reintentable, requiere corrección.', [
            'document_id' => $document->id,
            'fault_code' => $fault->faultCode,
            'action' => $action,
            'remedy' => $remedy,
        ]);
    }

    private function requeue(Document $document, QuipuLogger $logger, string $reason, string $detail): void
    {
        SendDocumentJob::dispatch($document->id);
        $this->line(sprintf('#%d: %s, reencolado.', $document->id, $reason));
        $logger->info('Reintento inteligente: comprobante reencolado.', [
            'document_id' => $document->id,
            'reason' => $reason,
            'detail' => $detail,
        ]);
    }
}
