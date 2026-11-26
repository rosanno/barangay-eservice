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
        Schema::create('household_surveys', function (Blueprint $t) {
            $t->id();
            $t->foreignId('census_campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('census_campaign_household_id')->constrained()->cascadeOnDelete();
            $t->foreignId('enumerator_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedBigInteger('household_id')->nullable()->index();

            // draft | for_verification | needs_revision | approved
            $t->string('status')->default('draft');
            $t->unsignedTinyInteger('current_step')->default(1);

            // Section A
            $t->string('household_number')->nullable();
            $t->string('head_name')->nullable();
            $t->text('address')->nullable();
            $t->string('purok')->nullable();
            $t->string('housing_type')->nullable();
            $t->string('ownership_status')->nullable();

            // Section D
            $t->string('housing_condition')->nullable();
            $t->string('water_source')->nullable();
            $t->boolean('has_electricity')->nullable();
            $t->string('sanitation')->nullable();
            $t->json('extra_answers')->nullable();

            // Workflow
            $t->timestamp('submitted_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('verified_at')->nullable();
            $t->text('verification_notes')->nullable();
            $t->unsignedTinyInteger('revision_count')->default(0);
            $t->timestamps();

            $t->unique('census_campaign_household_id', 'hs_target_unique'); // one survey per target
            $t->index('status');
        });

        Schema::create('household_survey_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('household_survey_id')->constrained()->cascadeOnDelete();
            $t->string('client_key', 36);
            $t->unsignedBigInteger('resident_id')->nullable()->index();
            $t->boolean('is_new_resident')->default(false);

            // Section B
            $t->string('first_name')->nullable();
            $t->string('middle_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('suffix', 20)->nullable();
            $t->string('relationship')->nullable();
            $t->string('sex')->nullable();
            $t->date('birthdate')->nullable();

            // Section C
            $t->string('civil_status')->nullable();
            $t->string('education')->nullable();
            $t->string('employment_status')->nullable();
            $t->string('occupation')->nullable();
            $t->boolean('is_pwd')->default(false);
            $t->boolean('is_registered_voter')->default(false);
            $t->string('contact_number', 30)->nullable();
            $t->timestamps();

            $t->unique(['household_survey_id', 'client_key'], 'hsm_survey_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_survey_members');
        Schema::dropIfExists('household_surveys');
    }
};