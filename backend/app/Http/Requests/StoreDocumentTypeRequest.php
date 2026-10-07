<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Route param is 'documentType' when editing (PATCH), absent when
        // creating (POST) — ignore the current row's own code on update so
        // saving without changing the code doesn't trip the uniqueness rule.
        $documentType = $this->route('documentType');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('document_types', 'code')->ignore($documentType?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'fee' => ['required', 'numeric', 'min:0'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:60'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:150'],
            'is_active' => ['boolean'],
        ];
    }
}