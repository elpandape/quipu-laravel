<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Factory;

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\QuipuLaravel\Enums\State;
use ElPandaPe\QuipuLaravel\Models\Document;

/**
 * Builds Document rows for the tests, with sane defaults so each test only
 * spells out the attributes it cares about.
 */
final class DocumentFactory
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function attributes(array $overrides = []): array
    {
        return array_merge([
            'tenant_id' => null,
            'document_type' => DocumentType::Invoice,
            'series' => 'F001',
            'number' => 1,
            'state' => State::Draft,
            'issued_at' => '2026-07-17 10:00:00',
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function create(array $overrides = []): Document
    {
        return Document::query()->create(self::attributes($overrides));
    }
}
