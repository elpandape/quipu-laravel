<?php

declare(strict_types=1);

use ElPandaPe\QuipuLaravel\Tests\Support\CustomColumnQuipuTenant;
use ElPandaPe\QuipuLaravel\Tests\Support\QuipuEmitterTenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // The "encrypted" casts need an application key; set a deterministic one and
    // drop any encrypter resolved without it.
    config()->set('app.key', 'base64:' . base64_encode(str_repeat('q', 32)));
    app()->forgetInstance('encrypter');

    Schema::dropIfExists('app_tenants');
    Schema::create('app_tenants', function (Blueprint $table): void {
        $table->id();
        $table->string('quipu_ruc');
        $table->string('quipu_legal_name');
        $table->string('quipu_trade_name')->nullable();
        $table->string('quipu_sol_user');
        $table->text('quipu_sol_pass');
        $table->text('quipu_certificate');
        $table->text('quipu_certificate_passphrase')->nullable();
        $table->decimal('quipu_igv_rate', 5, 2)->nullable();
        $table->string('quipu_series_prefix')->nullable();
        $table->string('quipu_disk')->nullable();
    });
});

it('mapea las columnas convencionales y cifra las credenciales', function (): void {
    $tenant = QuipuEmitterTenant::query()->create([
        'quipu_ruc' => '20123456789',
        'quipu_legal_name' => 'ACME SAC',
        'quipu_trade_name' => 'ACME',
        'quipu_sol_user' => 'ACMEUSER',
        'quipu_sol_pass' => 'sol-secret',
        'quipu_certificate' => 'PEM-BODY',
        'quipu_certificate_passphrase' => 'cert-secret',
        'quipu_igv_rate' => 8.0,
        'quipu_series_prefix' => 'F',
        'quipu_disk' => 'acme-disk',
    ]);

    expect($tenant->quipuRuc())->toBe('20123456789')
        ->and($tenant->quipuLegalName())->toBe('ACME SAC')
        ->and($tenant->quipuTradeName())->toBe('ACME')
        ->and($tenant->quipuSolUser())->toBe('ACMEUSER')
        ->and($tenant->quipuSolPass())->toBe('sol-secret')
        ->and($tenant->quipuCertificatePem())->toBe('PEM-BODY')
        ->and($tenant->quipuCertificatePassphrase())->toBe('cert-secret')
        ->and($tenant->quipuIgvRate())->toBe(8.0)
        ->and($tenant->quipuSeriesPrefix())->toBe('F')
        ->and($tenant->quipuStorageDisk())->toBe('acme-disk');

    // Credentials are ciphertext at rest, decrypted transparently on read above.
    $rawCert = DB::table('app_tenants')->where('id', $tenant->getKey())->value('quipu_certificate');
    $rawSol = DB::table('app_tenants')->where('id', $tenant->getKey())->value('quipu_sol_pass');
    $rawPass = DB::table('app_tenants')->where('id', $tenant->getKey())->value('quipu_certificate_passphrase');

    expect($rawCert)->not->toBe('PEM-BODY')
        ->and($rawSol)->not->toBe('sol-secret')
        ->and($rawPass)->not->toBe('cert-secret');
});

it('devuelve null en las columnas opcionales ausentes', function (): void {
    $tenant = QuipuEmitterTenant::query()->create([
        'quipu_ruc' => '20000000001',
        'quipu_legal_name' => 'MINI SAC',
        'quipu_sol_user' => 'U',
        'quipu_sol_pass' => 'p',
        'quipu_certificate' => 'PEM',
    ]);

    expect($tenant->quipuTradeName())->toBeNull()
        ->and($tenant->quipuCertificatePassphrase())->toBeNull()
        ->and($tenant->quipuIgvRate())->toBeNull()
        ->and($tenant->quipuSeriesPrefix())->toBeNull()
        ->and($tenant->quipuStorageDisk())->toBeNull();
});

it('honra los nombres de columna personalizados, incluso cifrados', function (): void {
    Schema::dropIfExists('org');
    Schema::create('org', function (Blueprint $table): void {
        $table->id();
        $table->string('ruc');
        $table->text('cert');
        $table->string('storage_disk')->nullable();
    });

    $tenant = CustomColumnQuipuTenant::query()->create([
        'ruc' => '20999999999',
        'cert' => 'CUSTOM-PEM',
        'storage_disk' => 'org-disk',
    ]);

    expect($tenant->quipuRuc())->toBe('20999999999')
        ->and($tenant->quipuCertificatePem())->toBe('CUSTOM-PEM')
        ->and($tenant->quipuStorageDisk())->toBe('org-disk');

    $rawCert = DB::table('org')->where('id', $tenant->getKey())->value('cert');
    expect($rawCert)->not->toBe('CUSTOM-PEM');
});
