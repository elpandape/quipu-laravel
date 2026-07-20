<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use ElPandaPe\QuipuLaravel\Emitter\EmitterConfig;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Tests\Factory\EmitterConfigFactory;

/**
 * A custom EmitterConfigResolver used to exercise the "<class>" tenancy driver:
 * config('quipu.tenancy.driver') set to this class name must be resolved from
 * the container and returned as the active-emitter resolver.
 */
final class CustomEmitterConfigResolver implements EmitterConfigResolver
{
    public function resolve(): EmitterConfig
    {
        return EmitterConfigFactory::make();
    }
}
