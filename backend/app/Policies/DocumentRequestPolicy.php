<?php

namespace App\Policies;

use App\Models\DocumentRequest;
use App\Models\User;

class DocumentRequestPolicy
{
    /**
     * Staff/admin listing of ALL requests (used to also gate "sensitive" resource fields).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'staff']);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->id === $documentRequest->user_id || $user->hasAnyRole(['admin', 'staff']);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function cancel(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->id === $documentRequest->user_id;
    }

    public function updateStatus(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->hasAnyRole(['admin', 'staff']);
    }
}
