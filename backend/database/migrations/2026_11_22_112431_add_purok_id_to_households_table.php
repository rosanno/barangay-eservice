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
        Schema::table('households', function (Blueprint $t) {
            $t->foreignId('purok_id')->nullable()->after('user_id')
                ->constrained('puroks')->nullOnDelete();

            // Legacy text column: no longer required, kept until fully migrated
            $t->string('purok', 100)->nullable()->change();

            $t->index(['status', 'purok_id']);
        });

        // Backfill: normalize case/spacing so variants collapse into one purok
        $map = []; // normalized key => purok id

        DB::table('households')->whereNotNull('purok')->orderBy('id')
            ->each(function ($h) use (&$map) {
                $name = preg_replace('/\s+/', ' ', trim($h->purok));
                if ($name === '')
                    return;

                $key = mb_strtolower($name);

                if (!isset($map[$key])) {
                    $map[$key] = DB::table('puroks')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'name' => Str::title($name),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('households')->where('id', $h->id)->update(['purok_id' => $map[$key]]);
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('households', function (Blueprint $t) {
            $t->dropIndex(['status', 'purok_id']);
            $t->dropConstrainedForeignId('purok_id');
        });
    }
};
