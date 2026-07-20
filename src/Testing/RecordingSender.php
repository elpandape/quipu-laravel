<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Testing;

use ElPandaPe\Quipu\Contract\Sender;
use ElPandaPe\Quipu\Result\BillResult;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\Quipu\Result\TicketResult;

/**
 * The internal Sender double used by Quipu::fake() when the Pro edition is not
 * installed: returns a configurable CDR instead of hitting SUNAT and records the
 * signed documents it was asked to send. It mirrors Pro's shippable FakeSender so
 * a consumer's tests behave the same with or without the Pro edition.
 */
final class RecordingSender implements Sender
{
    private CdrResult $cdr;

    /** @var list<mixed> */
    private array $recorded = [];

    public function __construct()
    {
        $this->cdr = new CdrResult(CdrStatus::Accepted, '0', 'Aceptado');
    }

    public function willAccept(): self
    {
        $this->cdr = new CdrResult(CdrStatus::Accepted, '0', 'Aceptado');

        return $this;
    }

    public function willReject(string $code = '2223', string $description = 'Rechazado'): self
    {
        $this->cdr = new CdrResult(CdrStatus::Rejected, $code, $description);

        return $this;
    }

    /** @param list<string> $notes */
    public function willObserve(array $notes): self
    {
        $this->cdr = new CdrResult(CdrStatus::AcceptedWithObservations, '0', 'Aceptado con observaciones', $notes);

        return $this;
    }

    /** @return list<mixed> */
    public function recorded(): array
    {
        return $this->recorded;
    }

    public function sendBill(SignedXml $signedXml): BillResult
    {
        $this->recorded[] = $signedXml;

        return new BillResult($this->cdr);
    }

    public function sendSummary(SignedXml $signedXml): TicketResult
    {
        $this->recorded[] = $signedXml;

        return new TicketResult('FAKE-TICKET');
    }

    /** @param list<SignedXml> $documents */
    public function sendPack(array $documents, string $batchName): TicketResult
    {
        $this->recorded[] = $batchName;

        return new TicketResult('FAKE-TICKET');
    }

    public function getStatus(string $ticket): CdrResult
    {
        $this->recorded[] = $ticket;

        return $this->cdr;
    }

    /** @return array<string, CdrResult> */
    public function getPackStatus(string $ticket): array
    {
        $this->recorded[] = $ticket;

        return ['FAKE-FILE' => $this->cdr];
    }
}
