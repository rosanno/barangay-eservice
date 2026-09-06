<?php

namespace App\Policies;

use App\Models\DocumentRequest;
use App\Models\User;

class DocumentRequestPolicy
{
    private function isStaffOrAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    /**
     * Staff/admin listing of ALL requests (used to also gate "sensitive" resource fields).
     */
    public function viewAny(User $user): bool
    {
        return $this->isStaffOrAdmin($user);
    }

    public function view(User $user, DocumentRequest $documentRequest): bool
    {
        return $user->id === $documentRequest->user_id || $this->isStaffOrAdmin($user);
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
        return $this->isStaffOrAdmin($user);
    }
}