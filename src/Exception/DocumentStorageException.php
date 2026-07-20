<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Exception;

use RuntimeException;

/**
 * Thrown when a file cannot be read from the configured storage disk (for
 * example a CDR or inbox document that is not there).
 */
final class DocumentStorageException extends RuntimeException {}
