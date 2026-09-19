<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:255'],
            // 'after:now' keeps someone from booking a slot in the past;
            // there's deliberately no business-hours/weekday restriction
            // yet — add one here if walk-in hours need enforcing.
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
