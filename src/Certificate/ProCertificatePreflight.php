<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

use ElPandaPe\Quipu\Contract\Clock;
use ElPandaPe\QuipuPro\Certificate\CertificateInspector;
use ElPandaPe\QuipuPro\Certificate\PreFlightChecker;

/**
 * Pro pre-flight adapter: reads the PEM with Pro's {@see CertificateInspector}
 * and runs Pro's {@see PreFlightChecker} against the emisor's RUC and the clock.
 * Only instantiated when the Pro edition is active, so referencing the Pro
 * classes here is safe on a Lite-only install.
 */
final readonly class ProCertificatePreflight implements CertificatePreflight
{
    public function __construct(
        private Clock $clock,
        private CertificateInspector $inspector = new CertificateInspector(),
        private PreFlightChecker $checker = new PreFlightChecker(),
    ) {}

    public function errors(string $pem, string $emitterRuc): array
    {
        return $this->checker->errorsFor($this->inspector->inspect($pem), $emitterRuc, $this->clock);
    }
}
