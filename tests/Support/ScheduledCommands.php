<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;

/** Reads back the quipu commands registered on a Laravel Schedule. */
final class ScheduledCommands
{
    /**
     * A freshly resolved Schedule, so the service provider's callAfterResolving
     * re-runs against the config set in the current test.
     */
    public static function fresh(): Schedule
    {
        $container = Container::getInstance();
        $container->forgetInstance(Schedule::class);

        return $container->make(Schedule::class);
    }

    /** @return list<string> */
    public static function quipu(Schedule $schedule): array
    {
        $commands = [];
        foreach ($schedule->events() as $event) {
            if (is_string($event->command) && str_contains($event->command, 'quipu:')) {
                $commands[] = $event->command;
            }
        }

        return $commands;
    }
}
