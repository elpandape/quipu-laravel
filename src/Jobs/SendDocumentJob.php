<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Jobs;

use ElPandaPe\QuipuLaravel\Dispatching\DocumentDispatcher;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reports a persisted, already-signed document to SUNAT off the request cycle.
 * Runs on the connection from config('quipu.queue.connection'). The document is
 * looked up by id and only sent while it is still Signed, so a re-dispatch (for
 * example the scheduled retry) never double-sends an already resolved one.
 */
final class SendDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $documentId)
    {
        $connection = config('quipu.queue.connection');

        if (is_string($connection)) {
            $this->onConnection($connection);
        }
    }

    public function handle(DocumentDispatcher $dispatcher): void
    {
        $document = Document::query()->find($this->documentId);

        if (!$document instanceof Document || $document->state !== State::Signed) {
            return;
        }

        $dispatcher->send($document);
    }
}
