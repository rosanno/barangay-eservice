<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('census_campaigns', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status')->default('draft'); // draft|active|paused|completed
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index('status');
        });

        // Target puroks
        Schema::create('census_campaign_puroks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('census_campaign_id')->constrained()->cascadeOnDelete();
            $t->string('purok');
            $t->unique(['census_campaign_id', 'purok'], 'ccp_campaign_purok_unique');
        });

        // Assigned enumerators
        Schema::create('census_campaign_enumerators', function (Blueprint $t) {
            $t->id();
            $t->foreignId('census_campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('purok')->nullable(); // optional: limit an enumerator to one purok
            $t->timestamps();
            $t->unique(['census_campaign_id', 'user_id'], 'cce_campaign_user_unique');
        });

        // Target households + per-campaign progress
        Schema::create('census_campaign_households', function (Blueprint $t) {
            $t->id();
            $t->foreignId('census_campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('household_id')->constrained()->cascadeOnDelete();
            $t->foreignId('enumerator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status')->default('pending'); // pending|surveyed
            $t->timestamp('surveyed_at')->nullable();
            $t->timestamps();
            $t->unique(['census_campaign_id', 'household_id'], 'cch_campaign_household_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('census_campaign_households');
        Schema::dropIfExists('census_campaign_enumerators');
        Schema::dropIfExists('census_campaign_puroks');
        Schema::dropIfExists('census_campaigns');
    }
};