<?php

namespace Database\Seeders;

use App\Models\DocumentRequest;
use App\Notifications\DocumentRequestStatusUpdated;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Inserts directly into the notifications table rather than calling
     * ->notify(). DocumentRequestStatusUpdated implements ShouldQueue, so
     * calling notify() here would only guarantee a row appears if
     * QUEUE_CONNECTION=sync — with a real queue driver and no worker
     * running, the job would just sit pending and this seeder would
     * silently produce nothing. Inserting directly sidesteps that
     * entirely and always produces exactly 10 rows.
     */
    public function run(): void
    {
        $requests = DocumentRequest::with(['user', 'documentType'])
            ->latest()
            ->limit(10)
            ->get();

        if ($requests->isEmpty()) {
            $this->command?->warn(
                'No document requests found — run DocumentRequestSeeder first.'
            );
            return;
        }

        $rows = $requests->map(function (DocumentRequest $request) {
            $createdAt = Carbon::now()->subDays(fake()->numberBetween(0, 14))
                ->subHours(fake()->numberBetween(0, 23));
            $isRead = fake()->boolean(50);

            return [
                'id' => (string) Str::uuid(),
                'type' => DocumentRequestStatusUpdated::class,
                'notifiable_type' => get_class($request->user),
                'notifiable_id' => $request->user_id,
                'data' => json_encode([
                    'document_request_id' => $request->uuid,
                    'tracking_number' => $request->tracking_number,
                    'status' => $request->status->value,
                    'message' => "Your document request {$request->tracking_number} is now {$request->status->label()}.",
                ]),
                'read_at' => $isRead ? $createdAt->copy()->addHours(fake()->numberBetween(1, 12)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });

        DB::table('notifications')->insert($rows->all());

        $this->command?->info('Seeded ' . $rows->count() . ' notifications.');
    }
}