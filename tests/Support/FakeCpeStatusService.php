<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\CpeStatusService;
use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\Quipu\Result\BillConsultResult;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;

/** In-memory billConsultService double for the CDR re-download command tests. */
final class FakeCpeStatusService implements CpeStatusService
{
    public ?BillConsultResult $result = null;

    public ?TransportException $error = null;

    public function getStatus(string $ruc, string $documentType, string $series, int $number): BillConsultResult
    {
        return $this->respond();
    }

    public function getStatusCdr(string $ruc, string $documentType, string $series, int $number): BillConsultResult
    {
        return $this->respond();
    }

    private function respond(): BillConsultResult
    {
        if ($this->error instanceof TransportException) {
            throw $this->error;
        }

        return $this->result ?? CdrFactory::consult(withCdr: true);
    }
}
