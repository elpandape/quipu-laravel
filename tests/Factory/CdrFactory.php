<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Factory;

use ElPandaPe\Quipu\Result\BillConsultResult;
use ElPandaPe\Quipu\Result\CdrResult;
use ElPandaPe\Quipu\Result\CdrStatus;

/**
 * Canned CDR / billConsult results for the orchestration tests, so no SUNAT
 * round-trip is needed to exercise the accepted / observed / rejected branches.
 */
final class CdrFactory
{
    public static function accepted(): CdrResult
    {
        return new CdrResult(CdrStatus::Accepted, '0', 'La Factura numero F001-1, ha sido aceptada', xml: self::xml());
    }

    public static function observed(): CdrResult
    {
        return new CdrResult(
            CdrStatus::AcceptedWithObservations,
            '4000',
            'La Factura numero F001-1, ha sido aceptada con observaciones',
            notes: ['El dato ingresado como atributo no es válido.'],
            xml: self::xml(),
        );
    }

    public static function rejected(): CdrResult
    {
        return new CdrResult(CdrStatus::Rejected, '2335', 'El documento electrónico ingresado fue rechazado', xml: self::xml());
    }

    /** A rejected CDR carrying a specific SUNAT response code, to exercise the diagnosis. */
    public static function rejectedWithCode(string $responseCode): CdrResult
    {
        return new CdrResult(CdrStatus::Rejected, $responseCode, 'El documento electrónico ingresado fue rechazado', xml: self::xml());
    }

    /** An accepted CDR that carries no raw XML (nothing to persist). */
    public static function withoutXml(): CdrResult
    {
        return new CdrResult(CdrStatus::Accepted, '0', 'La Factura numero F001-1, ha sido aceptada');
    }

    public static function consult(bool $withCdr): BillConsultResult
    {
        return new BillConsultResult('0001', 'El comprobante existe y esta aceptado', $withCdr ? self::accepted() : null);
    }

    private static function xml(): string
    {
        return '<ar:ApplicationResponse xmlns:ar="urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2"/>';
    }
}
