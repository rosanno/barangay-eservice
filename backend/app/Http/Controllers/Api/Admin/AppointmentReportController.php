<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentReportController extends Controller
{
    /**
     * Shared filter set used by the list, the summary and the export,
     * so the table, the stat cards and the CSV always agree.
     */
    private function filtered(Request $request): Builder
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'purpose' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return Appointment::query()
            ->when($data['search'] ?? null, function (Builder $q, string $s) {
                $q->where(function (Builder $w) use ($s) {
                    $w->where('purpose', 'like', "%{$s}%")
                        ->orWhereHas('user', function (Builder $u) use ($s) {
                            $u->where('name', 'like', "%{$s}%")
                                ->orWhere('email', 'like', "%{$s}%");
                        });
                });
            })
            ->when($data['status'] ?? null, fn(Builder $q, string $v) => $q->where('status', $v))
            ->when($data['purpose'] ?? null, fn(Builder $q, string $v) => $q->where('purpose', $v))
            ->when($data['from'] ?? null, fn(Builder $q, string $v) => $q->whereDate('scheduled_at', '>=', $v))
            ->when($data['to'] ?? null, fn(Builder $q, string $v) => $q->whereDate('scheduled_at', '<=', $v));
    }

    /** GET /admin/reports/appointments */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $page = $this->filtered($request)
            ->with('user:id,name,email')
            ->orderByDesc('scheduled_at')
            ->paginate($perPage);

        return response()->json([
            'data' => [
                'items' => $page->items(),
                'total' => $page->total(),
                'page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
            ],
        ]);
    }

    /** GET /admin/reports/appointments/summary */
    public function summary(Request $request): JsonResponse
    {
        $base = $this->filtered($request);

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($n) => (int) $n);

        $completed = $byStatus['completed'] ?? 0;
        $cancelled = $byStatus['cancelled'] ?? 0;
        $noShow = $byStatus['no_show'] ?? 0;

        // Rates are measured against appointments that already have an outcome,
        // so upcoming ones don't drag the percentages down.
        $resolved = $completed + $cancelled + $noShow;
        $rate = fn(int $n) => $resolved > 0 ? round($n / $resolved * 100, 1) : 0;

        $daily = (clone $base)
            ->selectRaw('DATE(scheduled_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn($r) => ['day' => (string) $r->day, 'total' => (int) $r->total])
            ->values();

        $byPurpose = (clone $base)
            ->selectRaw('purpose, COUNT(*) as total')
            ->groupBy('purpose')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($r) => ['purpose' => $r->purpose, 'total' => (int) $r->total])
            ->values();

        $peak = (clone $base)
            ->selectRaw('HOUR(scheduled_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderByDesc('total')
            ->first();

        return response()->json([
            'data' => [
                'total' => (int) $byStatus->sum(),
                'by_status' => $byStatus,
                'completion_rate' => $rate($completed),
                'no_show_rate' => $rate($noShow),
                'daily' => $daily,
                'by_purpose' => $byPurpose,
                'peak_hour' => $peak ? (int) $peak->hour : null,
                // Unfiltered lists so the dropdowns always show every option.
                'purposes' => Appointment::query()
                    ->whereNotNull('purpose')
                    ->distinct()
                    ->orderBy('purpose')
                    ->pluck('purpose'),
                'statuses' => Appointment::query()
                    ->whereNotNull('status')
                    ->distinct()
                    ->orderBy('status')
                    ->pluck('status'),
            ],
        ]);
    }

    /** GET /admin/reports/appointments/export */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)
            ->with('user:id,name,email')
            ->orderByDesc('scheduled_at');

        $filename = 'appointment-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Filipino characters correctly

            fputcsv($out, ['ID', 'Resident', 'Email', 'Purpose', 'Date', 'Time', 'Status', 'Notes']);

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    $when = $a->scheduled_at ? Carbon::parse($a->scheduled_at) : null;

                    fputcsv($out, array_map([$this, 'safeCell'], [
                        $a->id,
                        $a->user?->name,
                        $a->user?->email,
                        $a->purpose,
                        $when?->toDateString(),
                        $when?->format('h:i A'),
                        $a->status,
                        $a->notes,
                    ]));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Stops spreadsheet formula injection from resident-entered text. */
    private function safeCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)
            ? "'" . $value
            : $value;
    }
}