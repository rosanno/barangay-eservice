<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\DocumentRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DocumentRequest;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportsAdminController extends Controller
{
    /**
     * GET /api/admin/reports
     *
     * Everything the Reports page needs in one call, rather than several
     * round trips for each chart — this data is small and cheap to compute
     * together.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'summary' => $this->summary(),
            'requests_by_status' => $this->requestsByStatus(),
            'requests_by_type' => $this->requestsByType(),
            'requests_over_time' => $this->requestsOverTime(14),
            'residents_by_purok' => $this->residentsByPurok(),
        ]);
    }

    private function summary(): array
    {
        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        return [
            'total_residents' => User::whereNotIn('role', ['admin', 'staff'])->count(),
            'total_requests' => DocumentRequest::count(),
            'total_appointments' => Appointment::count(),
            'fees_collected' => (float) DocumentRequest::where('payment_status', 'paid')->sum('fee'),
            'requests_this_month' => DocumentRequest::where('created_at', '>=', $thisMonthStart)->count(),
            'requests_last_month' => DocumentRequest::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count(),
        ];
    }

    /**
     * Every status is represented even at zero, so the bar chart on the
     * frontend doesn't have to guess which statuses exist.
     */
    private function requestsByStatus(): array
    {
        $counts = DocumentRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(DocumentRequestStatus::cases())->map(fn($status) => [
            'status' => $status->value,
            'label' => $status->label(),
            'count' => $counts->get($status->value, 0),
        ])->all();
    }

    private function requestsByType(): array
    {
        return DB::table('document_requests')
            ->join('document_types', 'document_requests.document_type_id', '=', 'document_types.id')
            ->select('document_types.name', DB::raw('count(*) as count'))
            ->groupBy('document_types.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->all();
    }

    /**
     * Daily counts for the last $days days, with zero-filled gaps so the
     * chart has one bar per day even on days nothing was submitted.
     */
    private function requestsOverTime(int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $counts = DocumentRequest::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))->map(function ($offset) use ($start, $counts) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();
            return [
                'date' => $key,
                'label' => $date->format('M j'),
                'count' => $counts->get($key, 0),
            ];
        })->all();
    }

    private function residentsByPurok(): array
    {
        return Resident::query()
            ->selectRaw('purok, count(*) as count')
            ->groupBy('purok')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => ['purok' => $row->purok, 'count' => (int) $row->count])
            ->all();
    }
}