<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\Document;
use ElPandaPe\Quipu\Contract\XmlBuilder;

/** Fake UBL builder returning a fixed XML string, so no real model is needed. */
final readonly class FakeXmlBuilder implements XmlBuilder
{
    public function __construct(public string $xml = '<Unsigned/>') {}

    public function build(Document $document): string
    {
        return $this->xml;
    }
}
