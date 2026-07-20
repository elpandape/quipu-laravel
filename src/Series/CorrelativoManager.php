<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Series;

use ElPandaPe\QuipuLaravel\Tenancy\TenantContext;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Carbon;

/**
 * Hands out the next correlativo (running number) for a document series,
 * atomically. One row per (tenant, document type, series) in the series table
 * (config('quipu.tables.series')) holds the last number issued; a locked
 * read-modify-write inside a transaction makes
 * concurrent callers serialize, so no number is ever handed out twice.
 *
 * The base install has no tenant (tenant_id is null on documents); series are
 * scoped by an empty-string sentinel here so the unique key stays effective
 * across every database.
 */
final readonly class CorrelativoManager
{
    public function __construct(
        private ConnectionResolverInterface $connections,
        private Repository $config,
        private TenantContext $tenants,
    ) {}

    /**
     * The next number for the given series, incrementing and persisting the
     * counter under a row lock. Concurrent calls never collide.
     *
     * With no explicit $tenantId the active tenant scopes the counter (per-tenant
     * correlativos); mono-tenant falls back to the empty-string sentinel.
     */
    public function next(string $documentType, string $series, ?string $tenantId = null): int
    {
        $connection = $this->connections->connection();
        $tenant = $tenantId ?? $this->tenants->currentTenantKey() ?? '';

        /** @var int $number */
        $number = $connection->transaction(
            fn(): int => $this->increment($connection, $documentType, $series, $tenant),
        );

        return $number;
    }

    private function increment(ConnectionInterface $connection, string $documentType, string $series, string $tenant): int
    {
        $table = $this->table();
        $now = Carbon::now();

        $connection->table($table)->insertOrIgnore([
            'tenant_id' => $tenant,
            'document_type' => $documentType,
            'series' => $series,
            'last_number' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $current = $connection->table($table)
            ->where('tenant_id', $tenant)
            ->where('document_type', $documentType)
            ->where('series', $series)
            ->lockForUpdate()
            ->value('last_number');

        $next = (is_numeric($current) ? (int) $current : 0) + 1;

        $connection->table($table)
            ->where('tenant_id', $tenant)
            ->where('document_type', $documentType)
            ->where('series', $series)
            ->update(['last_number' => $next, 'updated_at' => Carbon::now()]);

        return $next;
    }

    /** The configured series table (config('quipu.tables.series')). */
    private function table(): string
    {
        $table = $this->config->get('quipu.tables.series');

        return is_string($table) ? $table : 'quipu_series';
    }
}
