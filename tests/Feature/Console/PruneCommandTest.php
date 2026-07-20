<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('elimina del inbox lo más antiguo que N días y conserva lo reciente', function (): void {
    $disk = Storage::fake('local');
    $disk->put('inbox/viejo.xml', 'x');
    $disk->put('inbox/nuevo.xml', 'y');
    touch($disk->path('inbox/viejo.xml'), Carbon::now()->subDays(40)->getTimestamp());

    expect(Artisan::call('quipu:prune'))->toBe(0)
        ->and(Artisan::output())->toContain('Podados 1 archivo(s) del inbox.');

    expect($disk->exists('inbox/viejo.xml'))->toBeFalse()
        ->and($disk->exists('inbox/nuevo.xml'))->toBeTrue();
});

it('respeta --disk, --path y --days', function (): void {
    $disk = Storage::fake('nube');
    $disk->put('bandeja/a.xml', 'x');
    touch($disk->path('bandeja/a.xml'), Carbon::now()->subDays(40)->getTimestamp());

    expect(Artisan::call('quipu:prune', ['--disk' => 'nube', '--path' => 'bandeja', '--days' => 10]))->toBe(0);

    expect($disk->exists('bandeja/a.xml'))->toBeFalse();
});
