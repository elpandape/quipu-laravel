<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Testing;

use ElPandaPe\Quipu\Contract\GreSender;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\Quipu\Result\TicketResult;

/**
 * The internal GreSender double used by Quipu::fake() when the Pro edition is not
 * installed, so emitting a dispatch guide (GRE) never touches the network. It
 * returns a canned accepted CDR; the analogue to {@see RecordingSender}.
 */
final readonly class FakeGreSender implements GreSender
{
    private CdrResult $cdr;

    public function __construct()
    {
        $this->cdr = new CdrResult(CdrStatus::Accepted, '0', 'Aceptado');
    }

    public function sendGuide(string $fileName, SignedXml $signedXml): TicketResult
    {
        return new TicketResult('FAKE-GRE-TICKET');
    }

    public function guideStatus(string $ticket): CdrResult
    {
        return $this->cdr;
    }
}
