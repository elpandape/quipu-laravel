<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Testing;

use Closure;
use ElPandaPe\Quipu\Contract\GreSender;
use ElPandaPe\Quipu\Contract\Sender;
use ElPandaPe\Quipu\Quipu;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Testing\FakeGreSender as ProFakeGreSender;
use ElPandaPe\QuipuPro\Testing\FakeSender as ProFakeSender;
use Illuminate\Contracts\Container\Container;
use PHPUnit\Framework\Assert;

/**
 * The offline test double installed by Quipu::fake(): it swaps the container's
 * Quipu emitter (and its Sender/GreSender) for in-memory doubles, so a consumer
 * can exercise its SUNAT emission flow with no certificate and no network, then
 * assert what was sent — the Laravel idiom, like Mail::fake().
 *
 * When the Pro edition is active it reuses Pro's shippable testing toolkit
 * ({@see ProFakeSender}/{@see ProFakeGreSender}); otherwise it falls back to the
 * internal {@see RecordingSender}/{@see FakeGreSender}. Either way the assertions
 * behave the same, so a consumer's tests are edition-agnostic.
 */
final readonly class QuipuFake
{
    /**
     * @param Closure(): list<SignedXml> $sentReader
     * @param Closure(): void $onAccept
     * @param Closure(string, string): void $onReject
     * @param Closure(list<string>): void $onObserve
     */
    private function __construct(
        private Closure $sentReader,
        private Closure $onAccept,
        private Closure $onReject,
        private Closure $onObserve,
    ) {}

    /**
     * Build the fake doubles, bind them into the container (the Quipu emitter and
     * the Sender/GreSender contracts) and register the handle so the facade's
     * assertions can find it.
     */
    public static function bind(Container $container, ProDetector $detector): self
    {
        $builder = new FakeXmlBuilder();
        $signer = new FakeSigner();

        if ($detector->isActive()) {
            $sender = new ProFakeSender();
            $greSender = new ProFakeGreSender();
            $fake = new self(
                sentReader: static fn(): array => self::onlySigned($sender->sent()->all()),
                onAccept: static function () use ($sender): void {
                    $sender->willAccept();
                },
                onReject: static function (string $code, string $description) use ($sender): void {
                    $sender->willReject($code, $description);
                },
                onObserve: static function (array $notes) use ($sender): void {
                    $sender->willObserve($notes);
                },
            );
        } else {
            $sender = new RecordingSender();
            $greSender = new FakeGreSender();
            $fake = new self(
                sentReader: static fn(): array => self::onlySigned($sender->recorded()),
                onAccept: static function () use ($sender): void {
                    $sender->willAccept();
                },
                onReject: static function (string $code, string $description) use ($sender): void {
                    $sender->willReject($code, $description);
                },
                onObserve: static function (array $notes) use ($sender): void {
                    $sender->willObserve($notes);
                },
            );
        }

        $container->instance(Quipu::class, new Quipu(
            builder: $builder,
            signer: $signer,
            sender: $sender,
            greSender: $greSender,
        ));
        $container->instance(Sender::class, $sender);
        $container->instance(GreSender::class, $greSender);
        $container->instance(self::class, $fake);

        return $fake;
    }

    /** Make every fake submission return an accepted CDR (the default). */
    public function acceptsEverything(): self
    {
        ($this->onAccept)();

        return $this;
    }

    /** Make every fake submission return a rejected CDR with the given SUNAT code. */
    public function rejectsEverything(string $code = '2223', string $description = 'Rechazado'): self
    {
        ($this->onReject)($code, $description);

        return $this;
    }

    /**
     * Make every fake submission return an accepted-with-observations CDR.
     *
     * @param list<string> $notes
     */
    public function observesEverything(array $notes = ['Observación de prueba']): self
    {
        ($this->onObserve)($notes);

        return $this;
    }

    /**
     * Assert at least one document was sent to SUNAT. With a callback, assert at
     * least one of the sent (signed) documents satisfies it.
     *
     * @param (callable(SignedXml): bool)|null $callback
     */
    public function assertSent(?callable $callback = null): void
    {
        $sent = ($this->sentReader)();

        Assert::assertNotEmpty($sent, 'Se esperaba que se enviara al menos un comprobante a SUNAT, pero no se envió ninguno.');

        if ($callback === null) {
            return;
        }

        foreach ($sent as $signed) {
            if ($callback($signed)) {
                return;
            }
        }

        Assert::fail('Ningún comprobante enviado a SUNAT coincidió con la condición esperada.');
    }

    /** Assert nothing was sent to SUNAT. */
    public function assertNothingSent(): void
    {
        $sent = ($this->sentReader)();

        Assert::assertCount(0, $sent, sprintf('Se esperaba que no se enviara ningún comprobante, pero se enviaron %d.', count($sent)));
    }

    /** Assert exactly the given number of documents were sent to SUNAT. */
    public function assertSentCount(int $expected): void
    {
        $sent = ($this->sentReader)();

        Assert::assertCount($expected, $sent, sprintf('Se esperaban %d comprobantes enviados, pero se enviaron %d.', $expected, count($sent)));
    }

    /**
     * Keep only the signed documents from a fake's recorded payloads: a submission
     * (sendBill/sendSummary) records a SignedXml, while status polls record their
     * ticket string — the assertions count documents sent, not tickets polled.
     *
     * @param list<mixed> $payloads
     *
     * @return list<SignedXml>
     */
    private static function onlySigned(array $payloads): array
    {
        return array_values(array_filter($payloads, static fn(mixed $payload): bool => $payload instanceof SignedXml));
    }
}
