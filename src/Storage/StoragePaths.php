<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Storage;

/**
 * The logical folders inside the storage disk: signed XML we generate, CDR
 * returned by SUNAT, and an inbox for XML brought in by hand or from the portal.
 */
final readonly class StoragePaths
{
    public function __construct(
        public string $signed,
        public string $cdr,
        public string $inbox,
    ) {}
}
