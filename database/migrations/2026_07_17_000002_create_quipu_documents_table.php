<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $documents = config('quipu.tables.documents');
        $tickets = config('quipu.tables.tickets');
        assert(is_string($documents));
        assert(is_string($tickets));

        Schema::create($documents, function (Blueprint $table) use ($tickets): void {
            $table->id();
            // Reserved for the multi-tenant phase; null on a single-emitter install.
            $table->string('tenant_id')->nullable()->index();
            $table->string('document_type', 2);
            $table->string('series');
            $table->unsignedInteger('number');
            $table->string('state')->index();
            $table->timestamp('issued_at')->nullable();
            $table->string('signed_xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('digest')->nullable();
            $table->string('sunat_status')->nullable();
            $table->string('sunat_response_code')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained($tickets)->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'document_type', 'series', 'number']);
        });
    }

    public function down(): void
    {
        $documents = config('quipu.tables.documents');
        assert(is_string($documents));

        Schema::dropIfExists($documents);
    }
};
