<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class Storeadmindocumentrequestrequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The /admin prefix is already gated by the 'admin' middleware at
        // the route level; this is a defense-in-depth check, same pattern
        // as DocumentRequestPolicy::create() for the resident-facing route.
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
            'resident_id' => ['required', 'integer', 'exists:users,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'array'],
            'details.*' => ['nullable', 'string', 'max:1000'],

            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*.file' => ['required_with:attachments', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
            'attachments.*.label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
