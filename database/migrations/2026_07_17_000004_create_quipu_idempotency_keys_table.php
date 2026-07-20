<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $idempotency = config('quipu.tables.idempotency');
        assert(is_string($idempotency));

        Schema::create($idempotency, function (Blueprint $table): void {
            $table->id();
            $table->string('digest');
            $table->string('type');
            $table->text('result');
            $table->timestamps();
            $table->unique(['digest', 'type']);
        });
    }

    public function down(): void
    {
        $idempotency = config('quipu.tables.idempotency');
        assert(is_string($idempotency));

        Schema::dropIfExists($idempotency);
    }
};
