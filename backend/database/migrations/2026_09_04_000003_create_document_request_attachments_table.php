<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable(); // e.g. "Valid ID", "Proof of Billing"
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_attachments');
    }
};
