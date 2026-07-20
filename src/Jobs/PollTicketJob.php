<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Jobs;

use ElPandaPe\QuipuLaravel\Dispatching\TicketPoller;
use ElPandaPe\QuipuLaravel\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Polls an asynchronous SUNAT ticket off the request cycle, on the connection
 * from config('quipu.queue.connection'). While SUNAT is still processing (or the
 * query fails), the job releases itself back to the queue to be retried later.
 */
final class PollTicketJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const int RETRY_DELAY_SECONDS = 60;

    public function __construct(public int $ticketId)
    {
        $connection = config('quipu.queue.connection');

        if (is_string($connection)) {
            $this->onConnection($connection);
        }
    }

    public function handle(TicketPoller $poller): void
    {
        $ticket = Ticket::query()->find($this->ticketId);

        if (!$ticket instanceof Ticket) {
            return;
        }

        if (!$poller->poll($ticket)) {
            $this->release(self::RETRY_DELAY_SECONDS);
        }
    }
}
