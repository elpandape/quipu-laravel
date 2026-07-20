<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Factory;

use ElPandaPe\Quipu\Catalog\IdentityDocumentType;
use ElPandaPe\Quipu\Model\Client;

/**
 * Builds Client (acquirer) instances for the fluent-builder tests, with a sane
 * RUC default so each test only spells out what it cares about.
 */
final class ClientFactory
{
    public static function make(
        IdentityDocumentType $documentType = IdentityDocumentType::Ruc,
        string $documentNumber = '20123456789',
        string $legalName = 'CLIENTE SAC',
    ): Client {
        return new Client($documentType, $documentNumber, $legalName);
    }
}
