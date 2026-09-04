<?php

namespace App\Http\Requests;

use App\Enums\DocumentRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDocumentRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateStatus', $this->route('documentRequest')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(DocumentRequestStatus::class)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:status,' . DocumentRequestStatus::Rejected->value,
            ],
        ];
    }
}
