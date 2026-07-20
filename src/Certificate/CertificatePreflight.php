<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

/**
 * Seam for the Pro certificate pre-flight (RUC ↔ emisor match, key ≥ 2048 bits,
 * validity window, private-key presence). The base install binds a null-object
 * that reports "not applicable"; the Pro edition binds an adapter over Pro's
 * PreFlightChecker. Keeps DoctorCommand free of any Pro reference.
 */
interface CertificatePreflight
{
    /**
     * Pre-flight problems for the given PEM against the emisor's RUC. Returns
     * null when no pre-flight is available (Pro inactive), or a (possibly empty)
     * list of Spanish problem messages otherwise — empty meaning "ready to sign".
     *
     * @return list<string>|null
     */
    public function errors(string $pem, string $emitterRuc): ?array;
}
