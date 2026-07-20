<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tickets = config('quipu.tables.tickets');
        assert(is_string($tickets));

        Schema::create($tickets, function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('ticket');
            $table->string('document_type')->nullable();
            $table->string('state');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tickets = config('quipu.tables.tickets');
        assert(is_string($tickets));

        Schema::dropIfExists($tickets);
    }
};
