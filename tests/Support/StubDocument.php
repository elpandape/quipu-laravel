<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Catalog\DocumentType;
use ElPandaPe\Quipu\Contract\Document;

/**
 * A minimal domain document for the orchestration tests: it only needs to answer
 * documentType() and fileName(), since the build/sign step is faked.
 */
final readonly class StubDocument implements Document
{
    public function __construct(
        private DocumentType $documentType = DocumentType::Invoice,
        private string $fileName = '20000000001-01-F001-1',
    ) {}

    public function documentType(): DocumentType
    {
        return $this->documentType;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }
}
