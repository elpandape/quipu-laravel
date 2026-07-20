<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\Quipu\Contract\CpeStatusService;
use ElPandaPe\Quipu\Contract\Sender;
use ElPandaPe\Quipu\Contract\Signer;
use ElPandaPe\Quipu\Contract\XmlBuilder;

/**
 * The Lite building blocks of an emitter, resolved once from an EmitterConfig:
 * the UBL builder, the xmldsig signer, the SOAP sender and the billConsult
 * status client. Shared by the Lite EmitterFactory and the Pro ProEmitterFactory
 * so both wire the exact same components (the Pro one only adds resilience and
 * validators around them).
 */
final readonly class EmitterComponents
{
    public function __construct(
        public XmlBuilder $builder,
        public Signer $signer,
        public Sender $sender,
        public CpeStatusService $cpeStatusService,
    ) {}
}
