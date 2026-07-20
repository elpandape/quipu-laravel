<?php

declare(strict_types=1);

use ElPandaPe\Quipu\Result\BillResult;
use ElPandaPe\Quipu\Result\SignedXml;
use ElPandaPe\Quipu\Result\TicketResult;
use ElPandaPe\QuipuLaravel\Idempotency\DatabaseResultStore;
use ElPandaPe\QuipuLaravel\Tests\Factory\CdrFactory;
use ElPandaPe\QuipuLaravel\Tests\Support\FakeSender;
use ElPandaPe\QuipuPro\Idempotency\IdempotentSender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persiste y recupera un BillResult por digest', function (): void {
    $store = new DatabaseResultStore();
    $bill = new BillResult(CdrFactory::accepted());

    $store->putBill('DIGEST-A', $bill);

    expect($store->getBill('DIGEST-A'))->toEqual($bill);
});

it('devuelve null cuando el BillResult no está', function (): void {
    expect(new DatabaseResultStore()->getBill('AUSENTE'))->toBeNull();
});

it('persiste y recupera un TicketResult por digest', function (): void {
    $store = new DatabaseResultStore();
    $ticket = new TicketResult('TCKT-99');

    $store->putTicket('DIGEST-T', $ticket);

    expect($store->getTicket('DIGEST-T'))->toEqual($ticket);
});

it('devuelve null cuando el TicketResult no está', function (): void {
    expect(new DatabaseResultStore()->getTicket('AUSENTE'))->toBeNull();
});

it('no confunde un bill con un ticket bajo el mismo digest', function (): void {
    $store = new DatabaseResultStore();
    $store->putBill('MISMO', new BillResult(CdrFactory::accepted()));

    expect($store->getTicket('MISMO'))->toBeNull();
});

it('deduplica un reenvío dentro del IdempotentSender de Pro', function (): void {
    $inner = new FakeSender();
    $sender = new IdempotentSender($inner, new DatabaseResultStore());
    $signed = new SignedXml('<Signed/>', 'DIGEST-DEDUP');

    $sender->sendBill($signed);
    $sender->sendBill($signed);

    expect($inner->sendBillCalls)->toBe(1);
});
