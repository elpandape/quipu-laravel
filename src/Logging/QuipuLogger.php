<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Logging;

use Psr\Log\LoggerInterface;

/**
 * Thin, typed facade over the configured PSR-3 channel. Every entry is stamped
 * with a "component" => "quipu" context so the package's activity can be filtered
 * out of a shared log. Callers pass only safe scalars — never credentials, the
 * signing certificate or a document's XML.
 */
final readonly class QuipuLogger
{
    public function __construct(private LoggerInterface $logger) {}

    /** @param array<string, scalar|null> $context */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->decorate($context));
    }

    /** @param array<string, scalar|null> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->decorate($context));
    }

    /** @param array<string, scalar|null> $context */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->decorate($context));
    }

    /**
     * @param array<string, scalar|null> $context
     *
     * @return array<string, scalar|null>
     */
    private function decorate(array $context): array
    {
        return ['component' => 'quipu', ...$context];
    }
}
