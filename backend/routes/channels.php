<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Admin-only channel
Broadcast::channel('admin.dashboard', function (User $user) {
    return in_array($user->role, ['admin', 'staff']); // or $user->hasRole(...)
});

// Optional: each resident gets updates on their own requests
Broadcast::channel('resident.{id}', function (User $user, int $id) {
    return $user->id === $id;
});
