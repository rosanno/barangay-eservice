<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. BARANGAY_CLEARANCE
            $table->string('name');           // e.g. Barangay Clearance
            $table->text('description')->nullable();
            $table->decimal('fee', 8, 2)->default(0);
            $table->unsignedSmallInteger('processing_days')->default(1);
            $table->json('requirements')->nullable(); // e.g. ["Valid ID", "Proof of residency"]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
