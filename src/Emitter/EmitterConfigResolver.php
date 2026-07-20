<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

/**
 * Resolves the configuration of the active emitter. The default implementation
 * reads a single emitter from config('quipu'); a later Pro phase binds a
 * multi-tenant implementation in its place, so the container always builds the
 * emitter through this seam.
 */
interface EmitterConfigResolver
{
    public function resolve(): EmitterConfig;
}
