<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Enums\State;

it('permite las transiciones válidas del ciclo de vida', function (): void {
    expect(State::Draft->canTransitionTo(State::Signed))->toBeTrue()
        ->and(State::Signed->canTransitionTo(State::Sent))->toBeTrue()
        ->and(State::Sent->canTransitionTo(State::Accepted))->toBeTrue()
        ->and(State::Sent->canTransitionTo(State::Observed))->toBeTrue()
        ->and(State::Sent->canTransitionTo(State::Rejected))->toBeTrue()
        ->and(State::Accepted->canTransitionTo(State::Voided))->toBeTrue()
        ->and(State::Observed->canTransitionTo(State::Voided))->toBeTrue();
});

it('rechaza transiciones inválidas', function (): void {
    expect(State::Draft->canTransitionTo(State::Accepted))->toBeFalse()
        ->and(State::Draft->canTransitionTo(State::Voided))->toBeFalse()
        ->and(State::Sent->canTransitionTo(State::Draft))->toBeFalse()
        ->and(State::Signed->canTransitionTo(State::Accepted))->toBeFalse()
        ->and(State::Rejected->canTransitionTo(State::Voided))->toBeFalse()
        ->and(State::Voided->canTransitionTo(State::Accepted))->toBeFalse();
});

it('expone los estados terminales sin transiciones salientes', function (): void {
    expect(State::Rejected->allowedTransitions())->toBe([])
        ->and(State::Voided->allowedTransitions())->toBe([]);
});
