<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\Document;
use ElPandaPe\Quipu\Contract\DocumentReader;
use ElPandaPe\Quipu\Exception\InvalidDocumentException;

/** Fake reader returning a preset document (or throwing), bypassing UBL parsing. */
final readonly class FakeReader implements DocumentReader
{
    public function __construct(
        private Document $document,
        private ?InvalidDocumentException $error = null,
    ) {}

    public function read(string $xml): Document
    {
        if ($this->error instanceof InvalidDocumentException) {
            throw $this->error;
        }

        return $this->document;
    }
}
