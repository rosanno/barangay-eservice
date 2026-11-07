<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HouseholdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'purok' => ['required', 'string', 'max:100'],
            'street_address' => ['required', 'string', 'max:255'],

            'housing_tenure' => ['required', Rule::in(['owned', 'rented', 'shared', 'informal_settler'])],
            'house_material' => ['required', Rule::in(['concrete', 'semi_concrete', 'light_materials'])],
            'water_source' => ['required', Rule::in(['piped', 'deep_well', 'spring', 'delivered', 'other'])],
            'toilet_type' => ['required', Rule::in(['water_sealed', 'pit', 'shared', 'none'])],
            'has_electricity' => ['required', 'boolean'],

            'income_bracket' => ['nullable', Rule::in(['below_10k', '10k_20k', '20k_40k', 'above_40k'])],
            'is_4ps' => ['required', 'boolean'],

            'consent' => ['accepted'],

            'members' => ['required', 'array', 'min:1', 'max:30'],
            'members.*.first_name' => ['required', 'string', 'max:100'],
            'members.*.middle_name' => ['nullable', 'string', 'max:100'],
            'members.*.last_name' => ['required', 'string', 'max:100'],
            'members.*.suffix' => ['nullable', 'string', 'max:20'],
            'members.*.relationship_to_head' => ['required', 'string', 'max:50'],
            'members.*.is_head' => ['required', 'boolean'],
            'members.*.birthdate' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'members.*.sex' => ['required', Rule::in(['male', 'female'])],
            'members.*.civil_status' => ['required', Rule::in(['single', 'married', 'widowed', 'separated', 'live_in'])],
            'members.*.education' => ['nullable', 'string', 'max:100'],
            'members.*.occupation' => ['nullable', 'string', 'max:100'],
            'members.*.is_pwd' => ['required', 'boolean'],
            'members.*.is_solo_parent' => ['required', 'boolean'],
            'members.*.is_registered_voter' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $v) {
                $heads = collect($this->input('members', []))->filter(fn($m) => !empty($m['is_head']))->count();
                if ($heads !== 1) {
                    $v->errors()->add('members', 'Exactly one household head is required.');
                }
            },
        ];
    }
}
