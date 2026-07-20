<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Certificate;

/**
 * Resolves the signing certificate as PEM content (X.509 certificate + private
 * key) — never a local path, so the emitter also works on serverless / Laravel
 * Cloud. The default implementations read from an inline base64 env var, a
 * Laravel filesystem disk or a local file; a later Pro phase adds a
 * tenant-scoped (database) source in this same seam without touching the
 * EmitterFactory.
 */
interface CertificateResolver
{
    /**
     * The certificate as PEM content ready for the signer. Implementations
     * throw an EmitterConfigException when the certificate cannot be loaded.
     */
    public function resolvePem(): string;
}
