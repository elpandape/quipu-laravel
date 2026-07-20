<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\Quipu\Contract\Document;
use ElPandaPe\Quipu\Quipu;
use Illuminate\Container\Container;

/**
 * Assembles a real Lite Quipu from in-memory doubles and binds it into the
 * container, so the F3 orchestration is tested end to end without any crypto or
 * network. The doubles stay reachable for configuring responses and asserting
 * what was sent.
 */
final class FakeQuipu
{
    public FakeXmlBuilder $builder;

    public FakeSigner $signer;

    public FakeSender $sender;

    public FakeReader $reader;

    public FakeCpeStatusService $cpeStatus;

    public function __construct(?Document $readResult = null)
    {
        $this->builder = new FakeXmlBuilder();
        $this->signer = new FakeSigner();
        $this->sender = new FakeSender();
        $this->reader = new FakeReader($readResult ?? new StubDocument());
        $this->cpeStatus = new FakeCpeStatusService();
    }

    public function make(): Quipu
    {
        return new Quipu(
            $this->builder,
            $this->signer,
            $this->sender,
            reader: $this->reader,
            cpeStatusService: $this->cpeStatus,
        );
    }

    public function bind(): self
    {
        Container::getInstance()->instance(Quipu::class, $this->make());

        return $this;
    }
}
