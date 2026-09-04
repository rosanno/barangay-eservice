<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated resident may request a document.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'purpose' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'array'],
            'details.*' => ['nullable', 'string', 'max:1000'],

            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*.file' => ['required_with:attachments', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
            'attachments.*.label' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.file.mimes' => 'Attachments must be a JPG, PNG, or PDF file.',
            'attachments.*.file.max' => 'Each attachment must not exceed 5MB.',
        ];
    }
}
