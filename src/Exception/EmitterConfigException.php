<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Exception;

use RuntimeException;

/**
 * Thrown when the emitter cannot be built from the Laravel configuration:
 * an unknown environment, an unreadable certificate, or a private key that
 * cannot be decrypted with the configured passphrase.
 */
final class EmitterConfigException extends RuntimeException {}
