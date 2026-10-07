<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barangay_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Barangay San Roque');
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('office_hours')->nullable();
            $table->timestamps();
        });

        // Seed the single row immediately — the admin UI always works
        // against row id=1 rather than creating one on first save.
        DB::table('barangay_settings')->insert([
            'name' => 'Barangay San Roque',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('barangay_settings');
    }
};