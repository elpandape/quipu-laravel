<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

/**
 * No-op pre-flight bound when the Pro edition is inactive: reports "not
 * applicable" (null) so DoctorCommand keeps its basic behaviour unchanged.
 */
final readonly class NullCertificatePreflight implements CertificatePreflight
{
    public function errors(string $pem, string $emitterRuc): ?array
    {
        return null;
    }
}
