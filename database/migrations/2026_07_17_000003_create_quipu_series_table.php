<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $series = config('quipu.tables.series');
        assert(is_string($series));

        Schema::create($series, function (Blueprint $table): void {
            $table->id();
            // Empty-string sentinel for "no tenant" so the unique key below stays
            // effective even without multi-tenant (NULLs are distinct in a unique index).
            $table->string('tenant_id')->default('');
            $table->string('document_type', 2);
            $table->string('series');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type', 'series']);
        });
    }

    public function down(): void
    {
        $series = config('quipu.tables.series');
        assert(is_string($series));

        Schema::dropIfExists($series);
    }
};
