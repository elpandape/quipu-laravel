<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

/** Minimal UBL Invoice snippets for the XML inspection/diff command tests. */
final class XmlFixtures
{
    public static function invoice(string $id = 'F001-1'): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
                     xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
              <cbc:ID>{$id}</cbc:ID>
              <cbc:IssueDate>2026-07-17</cbc:IssueDate>
            </Invoice>
            XML;
    }
}
