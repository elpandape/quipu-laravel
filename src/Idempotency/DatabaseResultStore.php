<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Idempotency;

use ElPandaPe\Quipu\Result\BillResult;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrSeverity;
use ElPandaPe\Quipu\Result\CdrStatus;
use ElPandaPe\Quipu\Result\TicketResult;
use ElPandaPe\QuipuLaravel\Models\IdempotencyKey;
use ElPandaPe\QuipuPro\Idempotency\ResultStore;

/**
 * Persistent {@see ResultStore} backed by an Eloquent table: an accepted
 * submission's terminal result is cached under the signed document's xmldsig
 * digest, so a re-send of the same document short-circuits instead of hitting
 * SUNAT again — and, unlike Pro's in-memory default, the cache survives across
 * queue retries and redeploys. Only instantiated when the Pro edition is
 * active, so referencing the Pro interface here is safe.
 *
 * The cached result is stored with PHP serialize(); reads restrict
 * unserialize() to the known result value objects as defence in depth.
 */
final readonly class DatabaseResultStore implements ResultStore
{
    private const string TYPE_BILL = 'bill';

    private const string TYPE_TICKET = 'ticket';

    public function getBill(string $key): ?BillResult
    {
        $value = $this->read($key, self::TYPE_BILL, [
            BillResult::class,
            CdrResult::class,
            CdrStatus::class,
            CdrSeverity::class,
        ]);

        return $value instanceof BillResult ? $value : null;
    }

    public function putBill(string $key, BillResult $result): void
    {
        $this->write($key, self::TYPE_BILL, $result);
    }

    public function getTicket(string $key): ?TicketResult
    {
        $value = $this->read($key, self::TYPE_TICKET, [TicketResult::class]);

        return $value instanceof TicketResult ? $value : null;
    }

    public function putTicket(string $key, TicketResult $result): void
    {
        $this->write($key, self::TYPE_TICKET, $result);
    }

    /**
     * @param list<class-string> $allowed
     */
    private function read(string $key, string $type, array $allowed): mixed
    {
        $stored = IdempotencyKey::query()
            ->where('digest', $key)
            ->where('type', $type)
            ->value('result');

        if (!is_string($stored)) {
            return null;
        }

        return unserialize($stored, ['allowed_classes' => $allowed]);
    }

    private function write(string $key, string $type, object $result): void
    {
        IdempotencyKey::query()->updateOrCreate(
            ['digest' => $key, 'type' => $type],
            ['result' => serialize($result)],
        );
    }
}
