<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;
use ElPandaPe\QuipuLaravel\Logging\QuipuLogger;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuLaravel\Support\CarbonClock;
use ElPandaPe\QuipuPro\Certificate\CertificateException;
use ElPandaPe\QuipuPro\Certificate\CertificateInfo;
use ElPandaPe\QuipuPro\Certificate\CertificateInspector;

/**
 * Warns when the signing certificate is close to expiring (Pro's
 * CertificateInspector). The threshold defaults to
 * config('quipu.schedule.cert_expiry_days') and can be overridden with --days.
 * An expiry within the window (or already past) is logged as a warning/error;
 * otherwise a healthy line is printed. Meant for the scheduled Pro alert.
 */
final class CertificateExpiryCommand extends ProCommand
{
    private const int DEFAULT_THRESHOLD_DAYS = 30;

    /** @var string */
    protected $signature = 'quipu:cert:alert
        {--days= : Umbral de días antes de la expiración para alertar}';

    /** @var string */
    protected $description = 'Alerta cuando el certificado de firma está por expirar.';

    public function handle(ProDetector $detector, CertificateResolver $resolver, QuipuLogger $logger): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        try {
            $pem = $resolver->resolvePem();
            $info = new CertificateInspector()->inspect($pem);
        } catch (EmitterConfigException | CertificateException $exception) {
            $this->error(sprintf('Certificado: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $this->reportExpiry($info, $this->threshold(), $logger);

        return self::SUCCESS;
    }

    private function reportExpiry(CertificateInfo $info, int $threshold, QuipuLogger $logger): void
    {
        $now = new CarbonClock()->now();
        $expiry = gmdate('Y-m-d', $info->notAfter);

        if ($info->isExpiredAt($now)) {
            $message = sprintf('El certificado de firma venció el %s.', $expiry);
            $this->error($message);
            $logger->error($message, ['expires_at' => $expiry]);

            return;
        }

        $days = $info->daysUntilExpiry($now);
        if ($days <= $threshold) {
            $message = sprintf('El certificado de firma vence en %d día(s) (%s).', $days, $expiry);
            $this->warn($message);
            $logger->warning($message, ['days' => $days, 'expires_at' => $expiry]);

            return;
        }

        $this->info(sprintf('El certificado de firma vence en %d día(s) (%s); dentro del margen.', $days, $expiry));
    }

    private function threshold(): int
    {
        $option = $this->optionString('days');
        if ($option !== null && is_numeric($option)) {
            return (int) $option;
        }

        $configured = config('quipu.schedule.cert_expiry_days');

        return is_numeric($configured) ? (int) $configured : self::DEFAULT_THRESHOLD_DAYS;
    }
}
