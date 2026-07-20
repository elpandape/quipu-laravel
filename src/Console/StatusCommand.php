<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use ElPandaPe\QuipuLaravel\Dispatching\TicketPoller;
use ElPandaPe\QuipuLaravel\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

/**
 * Polls asynchronous SUNAT tickets. Given a ticket value it resolves that one;
 * with no argument it sweeps every still-pending ticket (the shape the scheduler
 * uses). Each resolution updates the ticket and its covered documents.
 */
final class StatusCommand extends Command
{
    /** @var string */
    protected $signature = 'quipu:status {ticket? : Ticket SUNAT a consultar; si se omite, sondea los pendientes}';

    /** @var string */
    protected $description = 'Consulta el estado de un ticket SUNAT (o de todos los pendientes).';

    public function handle(TicketPoller $poller): int
    {
        $tickets = $this->tickets();

        if (!$tickets instanceof \Illuminate\Database\Eloquent\Collection) {
            return self::FAILURE;
        }

        if ($tickets->isEmpty()) {
            $this->info('No hay tickets pendientes por consultar.');

            return self::SUCCESS;
        }

        foreach ($tickets as $ticket) {
            $resolved = $poller->poll($ticket);
            $this->line($resolved
                ? sprintf('Ticket %s: %s.', $ticket->ticket, $ticket->state)
                : sprintf('Ticket %s: aún en proceso.', $ticket->ticket));
        }

        return self::SUCCESS;
    }

    /** @return Collection<int, Ticket>|null null when a named ticket was not found */
    private function tickets(): ?Collection
    {
        $argument = $this->argument('ticket');

        if (is_string($argument) && $argument !== '') {
            $matches = Ticket::query()->where('ticket', $argument)->get();

            if ($matches->isEmpty()) {
                $this->error(sprintf('No se encontró el ticket "%s".', $argument));

                return null;
            }

            return $matches;
        }

        return Ticket::query()->where('state', Ticket::STATE_PENDING)->get();
    }
}
