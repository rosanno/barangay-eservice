<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('tracking_number')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();

            $table->string('purpose');
            $table->json('details')->nullable(); // extra form fields per document type (e.g. business name)

            $table->string('status')->default('pending');
            // pending -> processing -> ready_for_pickup -> released
            // pending/processing -> rejected
            // pending -> cancelled

            $table->decimal('fee', 8, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, waived

            $table->text('remarks')->nullable();       // internal staff notes
            $table->text('rejection_reason')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
