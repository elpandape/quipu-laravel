<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Testing;

use ElPandaPe\Quipu\Contract\Signer;
use ElPandaPe\Quipu\Result\SignedXml;

/**
 * The Signer double behind Quipu::fake(): returns a fixed SignedXml without any
 * certificate, so a consumer can test its emission flow with no cert on disk.
 */
final class FakeSigner implements Signer
{
    public function sign(string $xml): SignedXml
    {
        return new SignedXml('<signed/>', 'FAKE-DIGEST');
    }
}
