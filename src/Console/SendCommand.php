<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Jobs\SendDocumentJob;
use ElPandaPe\QuipuLaravel\Models\Document;
use Illuminate\Database\Eloquent\Collection;

/**
 * Reports signed-but-not-yet-sent documents to SUNAT: every pending one, or a
 * single document with --id. Without --sync each is queued through
 * SendDocumentJob; with --sync it is sent inline and its outcome printed.
 */
final class SendCommand extends QuipuCommand
{
    /** @var string */
    protected $signature = 'quipu:send
        {--id= : Enviar solo el comprobante con este id}
        {--sync : Enviar de forma síncrona en vez de encolar}';

    /** @var string */
    protected $description = 'Envía a SUNAT los comprobantes firmados pendientes (o uno por --id).';

    public function handle(DocumentDispatcher $dispatcher): int
    {
        $pending = $this->pending();

        if ($pending->isEmpty()) {
            $this->info('No hay comprobantes pendientes de envío.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');

        foreach ($pending as $document) {
            $sync ? $this->sendNow($dispatcher, $document) : $this->queue($document);
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

    private function queue(Document $document): void
    {
        SendDocumentJob::dispatch($document->id);
        $this->line(sprintf('Comprobante #%d encolado.', $document->id));
    }

    private function sendNow(DocumentDispatcher $dispatcher, Document $document): void
    {
        try {
            $sent = $dispatcher->send($document);
            $this->line(sprintf('Comprobante #%d: %s.', $sent->id, $sent->state->value));
        } catch (TransportException $exception) {
            $this->error(sprintf('Comprobante #%d: fallo de transporte — %s', $document->id, $exception->getMessage()));
        }
    }
}
