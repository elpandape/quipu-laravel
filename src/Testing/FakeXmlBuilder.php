<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Testing;

use ElPandaPe\Quipu\Contract\Document;
use ElPandaPe\Quipu\Contract\XmlBuilder;

/**
 * The XmlBuilder double behind Quipu::fake(): returns a fixed XML string so the
 * fake emitter can be assembled without touching the real UBL builder. What was
 * built is irrelevant to the fake — the signer and sender are faked too.
 */
final class FakeXmlBuilder implements XmlBuilder
{
    public function build(Document $document): string
    {
        return '<Document/>';
    }
}
