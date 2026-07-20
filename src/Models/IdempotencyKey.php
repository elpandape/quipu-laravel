<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A cached terminal SUNAT result, keyed by a signed document's xmldsig digest
 * and its kind ("bill" or "ticket"). Backs the Pro DatabaseResultStore so a
 * re-send of the same document short-circuits instead of hitting SUNAT again.
 *
 * @property int $id
 * @property string $digest
 * @property string $type
 * @property string $result serialized BillResult/TicketResult
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class IdempotencyKey extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'digest',
        'type',
        'result',
    ];

    /** The configured table name (config('quipu.tables.idempotency')). */
    public function getTable(): string
    {
        $table = config('quipu.tables.idempotency');

        return is_string($table) ? $table : 'quipu_idempotency_keys';
    }
}
