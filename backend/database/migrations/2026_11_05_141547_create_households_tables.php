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
        Schema::create('households', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('household_number')->nullable()->unique(); // HH-2026-00001, set after create
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // account that submitted it (one household per account)

            // Address
            $t->string('purok', 100);
            $t->string('street_address');

            // Housing & utilities
            $t->string('housing_tenure', 30);   // owned | rented | shared | informal_settler
            $t->string('house_material', 30);   // concrete | semi_concrete | light_materials
            $t->string('water_source', 30);     // piped | deep_well | spring | delivered | other
            $t->string('toilet_type', 30);      // water_sealed | pit | shared | none
            $t->boolean('has_electricity')->default(true);

            // Socio-economic
            $t->string('income_bracket', 30)->nullable(); // below_10k | 10k_20k | 20k_40k | above_40k
            $t->boolean('is_4ps')->default(false);

            // Workflow
            $t->string('status', 30)->default('pending'); // pending | verified | needs_update | rejected | inactive
            $t->text('remarks')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();

            // Data Privacy Act (RA 10173) consent
            $t->timestamp('consent_at')->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->index(['status', 'purok']);
        });

        Schema::create('household_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('household_id')->constrained()->cascadeOnDelete();

            $t->string('first_name', 100);
            $t->string('middle_name', 100)->nullable();
            $t->string('last_name', 100);
            $t->string('suffix', 20)->nullable();

            $t->string('relationship_to_head', 50);
            $t->boolean('is_head')->default(false);
            $t->date('birthdate');
            $t->string('sex', 10);               // male | female
            $t->string('civil_status', 30);      // single | married | widowed | separated | live_in
            $t->string('education', 100)->nullable();
            $t->string('occupation', 100)->nullable();

            $t->boolean('is_pwd')->default(false);
            $t->boolean('is_solo_parent')->default(false);
            $t->boolean('is_registered_voter')->default(false);

            $t->timestamps();

            $t->index(['household_id', 'is_head']);
            $t->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_members');
        Schema::dropIfExists('households');
    }
};
