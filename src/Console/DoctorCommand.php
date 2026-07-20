<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use Carbon\CarbonImmutable;
use ElPandaPe\QuipuLaravel\Certificate\CertificatePreflight;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfig;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Health check: the emitter configuration is complete, the signing certificate
 * is present and still in date, and SUNAT is reachable for the selected
 * environment. When the Pro edition is active it is enriched with the certificate
 * pre-flight (RUC ↔ emisor match, key ≥ 2048 bits, validity window) through the
 * CertificatePreflight seam; without Pro that seam is a null-object and the basic
 * checks stand alone.
 */
final class DoctorCommand extends Command
{
    private const int TIMEOUT_SECONDS = 5;

    /** @var string */
    protected $signature = 'quipu:doctor';

    /** @var string */
    protected $description = 'Comprueba la configuración, el certificado y la conectividad con SUNAT.';

    public function handle(
        EmitterConfigResolver $resolver,
        CertificateResolver $certificateResolver,
        CertificatePreflight $preflight,
    ): int {
        try {
            $config = $resolver->resolve();
        } catch (EmitterConfigException $exception) {
            $this->error(sprintf('Configuración inválida: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $healthy = $this->checkConfig($config);
        $healthy = $this->checkCertificate($certificateResolver, $config, $preflight) && $healthy;
        $healthy = $this->checkConnectivity($config) && $healthy;

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function checkConfig(EmitterConfig $config): bool
    {
        $missing = [];
        if ($config->ruc === '') {
            $missing[] = 'RUC';
        }
        if ($config->solUser === '') {
            $missing[] = 'usuario SOL';
        }
        if ($config->solPass === '') {
            $missing[] = 'clave SOL';
        }

        if ($missing !== []) {
            $this->error(sprintf('Configuración: falta %s.', implode(', ', $missing)));

            return false;
        }

        $this->info('Configuración: RUC y credenciales SOL presentes.');

        return true;
    }

    private function checkCertificate(
        CertificateResolver $certificateResolver,
        EmitterConfig $config,
        CertificatePreflight $preflight,
    ): bool {
        try {
            $pem = $certificateResolver->resolvePem();
        } catch (EmitterConfigException $exception) {
            $this->error(sprintf('Certificado: %s', $exception->getMessage()));

            return false;
        }

        $parsed = openssl_x509_parse($pem);
        if ($parsed === false) {
            $this->error('Certificado: no se pudo leer (formato inválido).');

            return false;
        }

        $validTo = $parsed['validTo_time_t'] ?? 0;
        $expiry = CarbonImmutable::createFromTimestamp(is_int($validTo) ? $validTo : 0);
        if ($expiry->isPast()) {
            $this->error(sprintf('Certificado: venció el %s.', $expiry->toDateString()));

            return false;
        }

        $this->info(sprintf('Certificado: vigente hasta %s.', $expiry->toDateString()));

        return $this->checkPreflight($preflight, $pem, $config->ruc);
    }

    /** The Pro certificate pre-flight; a no-op (and always healthy) without Pro. */
    private function checkPreflight(CertificatePreflight $preflight, string $pem, string $ruc): bool
    {
        $errors = $preflight->errors($pem, $ruc);
        if ($errors === null) {
            return true;
        }

        if ($errors === []) {
            $this->info('Pre-vuelo: el certificado cumple los requisitos de firma de SUNAT.');

            return true;
        }

        foreach ($errors as $error) {
            $this->error(sprintf('Pre-vuelo: %s', $error));
        }

        return false;
    }

    private function checkConnectivity(EmitterConfig $config): bool
    {
        $url = $config->billServiceEndpoint() . '?wsdl';

        try {
            Http::timeout(self::TIMEOUT_SECONDS)->get($url);
        } catch (ConnectionException) {
            $this->error(sprintf('Conectividad: no se pudo alcanzar %s.', $url));

            return false;
        }

        $this->info(sprintf('Conectividad: SUNAT respondió en %s.', $url));

        return true;
    }
}
