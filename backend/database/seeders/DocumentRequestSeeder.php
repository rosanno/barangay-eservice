<?php

namespace Database\Seeders;

use App\Enums\DocumentRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentRequestSeeder extends Seeder
{
    private const PURPOSES = [
        'Employment requirement',
        'School enrollment',
        'Loan application',
        'Business permit application',
        'Travel requirement',
        'Barangay ID application',
        'Medical assistance request',
        'Scholarship application',
        'Bank account opening',
        'Government transaction',
    ];

    private const REJECTION_REASONS = [
        'Attached ID is expired. Please resubmit with a valid ID.',
        'Proof of residency does not match the address on file.',
        'Incomplete requirements — please attach proof of billing.',
    ];

    public function run(): void
    {
        $residents = User::whereNotIn('role', ['admin', 'staff'])->pluck('id');
        $documentTypes = DocumentType::all();

        if ($residents->isEmpty() || $documentTypes->isEmpty()) {
            $this->command?->warn(
                'No residents and/or document types found — run ResidentSeeder and DocumentTypeSeeder first.'
            );
            return;
        }

        // Cycle through every status at least once (there are 6, we're
        // making 10), then fill the rest randomly — guarantees the Reports
        // page and status filters have something in every bucket to show,
        // rather than leaving it to chance.
        $statusCycle = DocumentRequestStatus::cases();
        $statuses = [];
        for ($i = 0; $i < 10; $i++) {
            $statuses[] = $i < count($statusCycle)
                ? $statusCycle[$i]
                : fake()->randomElement($statusCycle);
        }
        shuffle($statuses);

        DB::transaction(function () use ($residents, $documentTypes, $statuses) {
            foreach ($statuses as $status) {
                $this->createRequest($residents->random(), $documentTypes->random(), $status);
            }
        });

        $this->command?->info('Seeded 10 document requests across all statuses.');
    }

    private function createRequest($userId, DocumentType $documentType, DocumentRequestStatus $status): void
    {
        $requestedAt = Carbon::now()->subDays(fake()->numberBetween(1, 30))->subHours(fake()->numberBetween(0, 23));

        $processedAt = null;
        $readyAt = null;
        $releasedAt = null;
        $cancelledAt = null;
        $rejectionReason = null;
        $updatedAt = $requestedAt;
        $paymentStatus = PaymentStatus::Unpaid;

        switch ($status) {
            case DocumentRequestStatus::Processing:
                $processedAt = $requestedAt->copy()->addDay();
                $updatedAt = $processedAt;
                $paymentStatus = fake()->boolean(50) ? PaymentStatus::Paid : PaymentStatus::Unpaid;
                break;

            case DocumentRequestStatus::ReadyForPickup:
                $processedAt = $requestedAt->copy()->addDay();
                $readyAt = $processedAt->copy()->addDay();
                $updatedAt = $readyAt;
                $paymentStatus = fake()->boolean(70) ? PaymentStatus::Paid : PaymentStatus::Unpaid;
                break;

            case DocumentRequestStatus::Released:
                $processedAt = $requestedAt->copy()->addDay();
                $readyAt = $processedAt->copy()->addDay();
                $releasedAt = $readyAt->copy()->addDay();
                $updatedAt = $releasedAt;
                $paymentStatus = PaymentStatus::Paid;
                break;

            case DocumentRequestStatus::Rejected:
                // Half rejected straight from pending, half after processing
                // started — exercises both paths allowedTransitions() permits.
                if (fake()->boolean(50)) {
                    $processedAt = $requestedAt->copy()->addDay();
                    $updatedAt = $processedAt->copy()->addHours(3);
                } else {
                    $updatedAt = $requestedAt->copy()->addHours(6);
                }
                $rejectionReason = fake()->randomElement(self::REJECTION_REASONS);
                break;

            case DocumentRequestStatus::Cancelled:
                // Cancelled is only reachable directly from Pending, so
                // processed_at correctly stays null here.
                $cancelledAt = $requestedAt->copy()->addHours(fake()->numberBetween(1, 20));
                $updatedAt = $cancelledAt;
                break;

            case DocumentRequestStatus::Pending:
            default:
                // Nothing further — freshly requested, untouched since.
                break;
        }

        $documentRequest = new DocumentRequest([
            'user_id' => $userId,
            'document_type_id' => $documentType->id,
            'purpose' => fake()->randomElement(self::PURPOSES),
            'status' => $status,
            'fee' => $documentType->fee,
            'payment_status' => $paymentStatus,
            'rejection_reason' => $rejectionReason,
            'processed_at' => $processedAt,
            'ready_at' => $readyAt,
            'released_at' => $releasedAt,
            'cancelled_at' => $cancelledAt,
        ]);

        // Disable auto-timestamps so the backdated created_at/updated_at
        // below actually stick instead of being overwritten with now().
        $documentRequest->timestamps = false;
        $documentRequest->created_at = $requestedAt;
        $documentRequest->updated_at = $updatedAt;
        $documentRequest->save();
    }
}