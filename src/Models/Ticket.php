<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Models;

use ElPandaPe\Quipu\Catalog\DocumentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A SUNAT ticket for an asynchronous operation (daily summary, voided-documents
 * communication, batch). SUNAT returns the ticket immediately and the CDR is
 * fetched later; the documents it covers point back at it through ticket_id.
 *
 * @property int $id
 * @property ?string $tenant_id
 * @property string $ticket
 * @property ?DocumentType $document_type
 * @property string $state
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Document> $documents
 */
final class Ticket extends Model
{
    /** State of a ticket that has been issued by SUNAT but not yet resolved. */
    public const string STATE_PENDING = 'pending';

    /** @var array<int, string> */
    protected $fillable = [
        'tenant_id',
        'ticket',
        'document_type',
        'state',
    ];

    /** The configured table name (config('quipu.tables.tickets')). */
    public function getTable(): string
    {
        $table = config('quipu.tables.tickets');

        return is_string($table) ? $table : 'quipu_tickets';
    }

    /**
     * The documents this ticket tracks. One ticket may cover many documents
     * (a daily summary or a voided-documents batch).
     *
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'ticket_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
        ];
    }
}
