<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\Signer;
use ElPandaPe\Quipu\Result\SignedXml;

/** Fake signer returning a fixed signed XML and digest, without touching crypto. */
final readonly class FakeSigner implements Signer
{
    public function __construct(
        public string $signedXml = '<Signed/>',
        public string $digestValue = 'DIGEST==',
    ) {}

    public function sign(string $xml): SignedXml
    {
        return new SignedXml($this->signedXml, $this->digestValue);
    }
}
