<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Scheduling;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;

/**
 * Registers quipu's opt-in maintenance tasks on the Laravel scheduler: poll
 * pending tickets, retry documents still awaiting SUNAT, prune the inbox and
 * (when a default file is configured) send the prepared daily summary. Does
 * nothing unless config('quipu.schedule.enabled') is true. Each cron expression
 * is configurable.
 *
 * When the Pro edition is active the plain retry (quipu:send) is replaced by the
 * smart retry (quipu:pro:retry) and the certificate expiration alert
 * (quipu:cert:alert) is added.
 */
final readonly class ScheduleRegistrar
{
    public function __construct(private Repository $config) {}

    public function register(Schedule $schedule, bool $proActive = false): void
    {
        if ($this->config->get('quipu.schedule.enabled') !== true) {
            return;
        }

        $schedule->command('quipu:status')->cron($this->cron('poll_tickets_cron', '*/15 * * * *'));
        $schedule->command($proActive ? 'quipu:pro:retry' : 'quipu:send')->cron($this->cron('retry_pending_cron', '*/30 * * * *'));
        $schedule->command('quipu:prune')->cron($this->cron('prune_cron', '0 3 * * *'));

        $summaryFile = $this->config->get('quipu.schedule.daily_summary_file');
        if (is_string($summaryFile) && $summaryFile !== '') {
            $schedule->command('quipu:summary --file=' . $summaryFile)->cron($this->cron('daily_summary_cron', '0 1 * * *'));
        }

        if ($proActive) {
            $schedule->command('quipu:cert:alert')->cron($this->cron('cert_alert_cron', '0 8 * * *'));
        }
    }

    private function cron(string $key, string $default): string
    {
        $value = $this->config->get('quipu.schedule.' . $key);

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
