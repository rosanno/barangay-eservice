<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('details');
            $table->string('fingerprint', 40)->nullable()->after('ip_address');

            $table->string('flag_type')->nullable();            // duplicate | suspicious
            $table->unsignedTinyInteger('flag_score')->default(0);
            $table->json('flag_reasons')->nullable();
            $table->foreignId('duplicate_of_id')->nullable()
                ->constrained('document_requests')->nullOnDelete();
            $table->timestamp('flagged_at')->nullable();
            $table->timestamp('flag_reviewed_at')->nullable();
            $table->foreignId('flag_reviewed_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->index(['user_id', 'document_type_id', 'created_at']);
            $table->index('fingerprint');
            $table->index('flag_type');
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropConstrainedForeignId('flag_reviewed_by');
            $table->dropIndex(['user_id', 'document_type_id', 'created_at']);
            $table->dropIndex(['fingerprint']);
            $table->dropIndex(['flag_type']);
            $table->dropIndex(['ip_address', 'created_at']);
            $table->dropColumn([
                'ip_address',
                'fingerprint',
                'flag_type',
                'flag_score',
                'flag_reasons',
                'flagged_at',
                'flag_reviewed_at',
            ]);
        });
    }
};
