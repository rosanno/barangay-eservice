<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class AppointmentAdminController extends Controller
{
    /**
     * Appointments for a given day (defaults to today), ordered by time.
     * GET /api/admin/appointments?date=2026-09-10
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->string('date'))
            : Carbon::today();

        $appointments = Appointment::query()
            ->with('user')
            ->whereBetween('scheduled_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }
}