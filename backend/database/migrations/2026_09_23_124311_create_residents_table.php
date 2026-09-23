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
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();

            // One-to-one with the login/account record. A resident's
            // *account* (email, password, role) lives on users; everything
            // about who they are civically lives here.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('purok');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');

            $table->string('sex'); // male, female
            $table->date('date_of_birth');
            // No 'age' column on purpose — it's derived from date_of_birth
            // via an accessor on the model, so it can never go stale.
            $table->string('place_of_birth');
            $table->string('citizenship')->default('Filipino');
            $table->string('religion')->nullable();
            $table->string('blood_type')->nullable();

            $table->string('mother_first_name');
            $table->string('mother_middle_name')->nullable();
            $table->string('mother_last_name');
            $table->string('mother_occupation')->nullable();

            $table->string('father_first_name');
            $table->string('father_middle_name')->nullable();
            $table->string('father_last_name');
            $table->string('father_suffix')->nullable();
            $table->string('father_occupation')->nullable();

            $table->string('spouse_first_name')->nullable();
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_last_name')->nullable();
            $table->string('spouse_suffix')->nullable();
            $table->unsignedSmallInteger('number_of_children')->nullable();

            $table->string('emergency_contact_first_name');
            $table->string('emergency_contact_middle_name')->nullable();
            $table->string('emergency_contact_last_name');
            $table->string('emergency_contact_suffix')->nullable();
            $table->string('emergency_contact_number');

            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
