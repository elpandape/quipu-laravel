<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\Sender;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\Quipu\Result\BillResult;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\Quipu\Result\TicketResult;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;

/**
 * In-memory Sender double: returns configurable results, can be told to fail
 * with a TransportException, and records what it was asked to send — so the
 * orchestration is exercised without touching SUNAT.
 */
final class FakeSender implements Sender
{
    public ?CdrResult $billCdr = null;

    public ?CdrResult $statusCdr = null;

    public string $ticket = 'TCKT-0001';

    public ?TransportException $sendBillError = null;

    public ?TransportException $statusError = null;

    public int $sendBillCalls = 0;

    public ?SignedXml $lastSignedXml = null;

    public function sendBill(SignedXml $signedXml): BillResult
    {
        $this->sendBillCalls++;
        $this->lastSignedXml = $signedXml;

        if ($this->sendBillError instanceof TransportException) {
            throw $this->sendBillError;
        }

        return new BillResult($this->billCdr ?? CdrFactory::accepted());
    }

    public function sendSummary(SignedXml $signedXml): TicketResult
    {
        return new TicketResult($this->ticket);
    }

    /** @param list<SignedXml> $documents */
    public function sendPack(array $documents, string $batchName): TicketResult
    {
        return new TicketResult($this->ticket);
    }

    public function getStatus(string $ticket): CdrResult
    {
        if ($this->statusError instanceof TransportException) {
            throw $this->statusError;
        }

        return $this->statusCdr ?? CdrFactory::accepted();
    }

    /** @return array<string, CdrResult> */
    public function getPackStatus(string $ticket): array
    {
        return [];
    }
}
