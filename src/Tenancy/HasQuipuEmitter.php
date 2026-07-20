<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tenancy;

use Illuminate\Database\Eloquent\Model;

/**
 * Turns a host application's Tenant model into a {@see ProvidesQuipuEmitter} from
 * conventional columns, so wiring per-tenant emission is `use HasQuipuEmitter` on
 * a model that also `implements ProvidesQuipuEmitter` plus the columns below —
 * no accessor boilerplate.
 *
 * Default columns (each override-able by redeclaring the matching *Column()
 * method):
 *   quipu_ruc                     → quipuRuc()
 *   quipu_legal_name              → quipuLegalName()
 *   quipu_trade_name              → quipuTradeName()      (nullable)
 *   quipu_sol_user                → quipuSolUser()
 *   quipu_sol_pass                → quipuSolPass()         (encrypted at rest)
 *   quipu_certificate             → quipuCertificatePem()  (encrypted at rest)
 *   quipu_certificate_passphrase  → quipuCertificatePassphrase() (nullable, encrypted)
 *   quipu_igv_rate                → quipuIgvRate()         (nullable, decimal)
 *   quipu_series_prefix           → quipuSeriesPrefix()    (nullable)
 *   quipu_disk                    → quipuStorageDisk()     (nullable)
 *
 * The three credential columns get Laravel's "encrypted" cast (APP_KEY) merged
 * in automatically, so the certificate PEM, its passphrase and the SOL password
 * are encrypted in the database and decrypted transparently on read; this
 * package never sees the raw .pfx.
 *
 * @mixin Model
 */
trait HasQuipuEmitter
{
    /** Merge the encrypted casts for the credential columns onto the model. */
    public function initializeHasQuipuEmitter(): void
    {
        $this->mergeCasts([
            $this->quipuSolPassColumn() => 'encrypted',
            $this->quipuCertificateColumn() => 'encrypted',
            $this->quipuCertificatePassphraseColumn() => 'encrypted',
        ]);
    }

    public function quipuRuc(): string
    {
        return $this->quipuString($this->quipuRucColumn());
    }

    public function quipuLegalName(): string
    {
        return $this->quipuString($this->quipuLegalNameColumn());
    }

    public function quipuTradeName(): ?string
    {
        return $this->quipuNullableString($this->quipuTradeNameColumn());
    }

    public function quipuSolUser(): string
    {
        return $this->quipuString($this->quipuSolUserColumn());
    }

    public function quipuSolPass(): string
    {
        return $this->quipuString($this->quipuSolPassColumn());
    }

    public function quipuCertificatePem(): string
    {
        return $this->quipuString($this->quipuCertificateColumn());
    }

    public function quipuCertificatePassphrase(): ?string
    {
        return $this->quipuNullableString($this->quipuCertificatePassphraseColumn());
    }

    public function quipuIgvRate(): ?float
    {
        $value = $this->getAttribute($this->quipuIgvRateColumn());
        if ($value === null) {
            return null;
        }

        assert(is_numeric($value));

        return (float) $value;
    }

    public function quipuSeriesPrefix(): ?string
    {
        return $this->quipuNullableString($this->quipuSeriesPrefixColumn());
    }

    public function quipuStorageDisk(): ?string
    {
        return $this->quipuNullableString($this->quipuDiskColumn());
    }

    protected function quipuRucColumn(): string
    {
        return 'quipu_ruc';
    }

    protected function quipuLegalNameColumn(): string
    {
        return 'quipu_legal_name';
    }

    protected function quipuTradeNameColumn(): string
    {
        return 'quipu_trade_name';
    }

    protected function quipuSolUserColumn(): string
    {
        return 'quipu_sol_user';
    }

    protected function quipuSolPassColumn(): string
    {
        return 'quipu_sol_pass';
    }

    protected function quipuCertificateColumn(): string
    {
        return 'quipu_certificate';
    }

    protected function quipuCertificatePassphraseColumn(): string
    {
        return 'quipu_certificate_passphrase';
    }

    protected function quipuIgvRateColumn(): string
    {
        return 'quipu_igv_rate';
    }

    protected function quipuSeriesPrefixColumn(): string
    {
        return 'quipu_series_prefix';
    }

    protected function quipuDiskColumn(): string
    {
        return 'quipu_disk';
    }

    /** Read a required string attribute off the model (columns are string-typed). */
    private function quipuString(string $column): string
    {
        $value = $this->getAttribute($column);
        assert(is_string($value));

        return $value;
    }

    /** Read a nullable string attribute, normalising a missing value to null. */
    private function quipuNullableString(string $column): ?string
    {
        $value = $this->getAttribute($column);

        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return $value;
    }
}
