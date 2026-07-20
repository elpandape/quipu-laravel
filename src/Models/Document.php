<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Models;

use Carbon\CarbonImmutable;
use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Exception\InvalidStateTransitionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An electronic payment document (CPE) tracked by the integration: its SUNAT
 * identity (type/series/number), where its signed XML and CDR live, the SUNAT
 * outcome, and its lifecycle state. tenant_id is reserved for the multi-tenant
 * phase and stays null for a single-emitter install.
 *
 * @property int $id
 * @property ?string $tenant_id
 * @property DocumentType $document_type
 * @property string $series
 * @property int $number
 * @property State $state
 * @property ?CarbonImmutable $issued_at
 * @property ?string $signed_xml_path
 * @property ?string $cdr_path
 * @property ?string $digest
 * @property ?string $sunat_status
 * @property ?string $sunat_response_code
 * @property ?int $ticket_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ?Ticket $ticket
 */
final class Document extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'tenant_id',
        'document_type',
        'series',
        'number',
        'state',
        'issued_at',
        'signed_xml_path',
        'cdr_path',
        'digest',
        'sunat_status',
        'sunat_response_code',
        'ticket_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'state' => State::Draft->value,
    ];

    /** The configured table name (config('quipu.tables.documents')). */
    public function getTable(): string
    {
        $table = config('quipu.tables.documents');

        return is_string($table) ? $table : 'quipu_documents';
    }

    /**
     * The async ticket this document was submitted under, if any (summary,
     * voided-documents batch).
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Move the document to $target, enforcing the state machine. Persists the
     * new state. Rejects an illegal jump with a clear exception. Actual
     * emission (build/sign/send) is not done here.
     *
     * @throws InvalidStateTransitionException
     */
    public function transitionTo(State $target): void
    {
        if (!$this->state->canTransitionTo($target)) {
            throw new InvalidStateTransitionException($this->state, $target);
        }

        $this->state = $target;
        $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'state' => State::class,
            'issued_at' => 'immutable_datetime',
            'number' => 'integer',
        ];
    }
}
