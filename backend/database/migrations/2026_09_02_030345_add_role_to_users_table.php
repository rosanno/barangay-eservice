<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // resident = default self-registered barangay resident
            // staff    = barangay staff who can process requests
            // admin    = full system administrator
            $table->enum('role', ['resident', 'staff', 'admin'])
                ->default('resident')
                ->after('email');

            $table->enum('status', ['active', 'inactive', 'suspended'])
                ->default('active')
                ->after('role');

            $table->string('contact_number', 20)->nullable()->after('status');
            $table->string('address')->nullable()->after('contact_number');

            $table->foreignId('created_by')
                ->nullable()
                ->after('address')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['role', 'status', 'contact_number', 'address']);
        });
    }
};
