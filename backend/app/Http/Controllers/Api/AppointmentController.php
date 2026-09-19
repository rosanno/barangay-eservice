<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /**
     * The authenticated resident's own upcoming appointments.
     * GET /api/appointments
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $appointments = Appointment::query()
            ->where('user_id', $request->user()->id)
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = Appointment::create([
            'user_id' => $request->user()->id,
            'purpose' => $request->validated('purpose'),
            'scheduled_at' => $request->validated('scheduled_at'),
            'status' => AppointmentStatus::Scheduled,
        ]);

        return (new AppointmentResource($appointment))->response()->setStatusCode(201);
    }

    /**
     * A resident may cancel their own appointment while it's still
     * scheduled — mirrors DocumentRequest's resident-cancel pattern.
     */
    public function cancel(Request $request, Appointment $appointment): AppointmentResource
    {
        if ($appointment->user_id !== $request->user()->id) {
            throw ValidationException::withMessages(['appointment' => 'You may only cancel your own appointments.']);
        }

        if (!$appointment->status->canTransitionTo(AppointmentStatus::Cancelled)) {
            throw ValidationException::withMessages(['status' => 'This appointment can no longer be cancelled.']);
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return new AppointmentResource($appointment->fresh());
    }
}