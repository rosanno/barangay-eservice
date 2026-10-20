<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Only the fields listed here can ever change through this endpoint —
     * ProfileController::update() uses $request->validated(), so anything
     * else in the payload (name, date of birth, sex, purok, parents' names,
     * citizenship) is silently dropped even if a client sends it. Those are
     * identity records the barangay verified at registration; residents ask
     * the office to correct them.
     *
     * To let residents edit more, add the field here and to EDITABLE in
     * Profile.vue.
     */
    public function rules(): array
    {
        return [
            'religion' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],

            'mother_occupation' => ['nullable', 'string', 'max:150'],
            'father_occupation' => ['nullable', 'string', 'max:150'],

            'spouse_first_name' => ['nullable', 'string', 'max:100'],
            'spouse_middle_name' => ['nullable', 'string', 'max:100'],
            'spouse_last_name' => ['nullable', 'string', 'max:100'],
            'spouse_suffix' => ['nullable', 'string', 'max:20'],
            'number_of_children' => ['nullable', 'integer', 'min:0', 'max:50'],

            'emergency_contact_first_name' => ['required', 'string', 'max:100'],
            'emergency_contact_middle_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_last_name' => ['required', 'string', 'max:100'],
            'emergency_contact_suffix' => ['nullable', 'string', 'max:20'],
            'emergency_contact_number' => ['required', 'string', 'max:20'],
        ];
    }
}