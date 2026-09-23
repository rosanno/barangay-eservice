<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Sex;

class StoreResidentRequest extends FormRequest
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
            // Personal
            'purok' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'sex' => ['required', new Enum(Sex::class)],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'citizenship' => ['required', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', 'string', 'max:5'],

            // Mother
            'mother_first_name' => ['required', 'string', 'max:100'],
            'mother_middle_name' => ['nullable', 'string', 'max:100'],
            'mother_last_name' => ['required', 'string', 'max:100'],
            'mother_occupation' => ['nullable', 'string', 'max:150'],

            // Father
            'father_first_name' => ['required', 'string', 'max:100'],
            'father_middle_name' => ['nullable', 'string', 'max:100'],
            'father_last_name' => ['required', 'string', 'max:100'],
            'father_suffix' => ['nullable', 'string', 'max:20'],
            'father_occupation' => ['nullable', 'string', 'max:150'],

            // Spouse (fully optional — not everyone is married)
            'spouse_first_name' => ['nullable', 'string', 'max:100'],
            'spouse_middle_name' => ['nullable', 'string', 'max:100'],
            'spouse_last_name' => ['nullable', 'string', 'max:100'],
            'spouse_suffix' => ['nullable', 'string', 'max:20'],
            'number_of_children' => ['nullable', 'integer', 'min:0', 'max:50'],

            // Emergency contact
            'emergency_contact_first_name' => ['required', 'string', 'max:100'],
            'emergency_contact_middle_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_last_name' => ['required', 'string', 'max:100'],
            'emergency_contact_suffix' => ['nullable', 'string', 'max:20'],
            'emergency_contact_number' => ['required', 'string', 'max:20'],

            // Account credentials — separate concern from the profile above
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
