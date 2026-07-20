<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\TestCase;

// Feature tests boot a Laravel application through testbench; Unit tests stay
// plain (no framework) so the emitter logic is exercised in isolation.
pest()->extend(TestCase::class)->in('Feature');
