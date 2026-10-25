<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'email_request_updates',
    ];

    protected function casts(): array
    {
        return [
            'email_request_updates' => 'boolean',
        ];
    }

    /**
     * Rows are created lazily the first time anything asks, with every
     * preference defaulting to on — so existing users keep getting emails
     * exactly as before until they choose otherwise.
     */
    public static function forUser(object $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id],
            ['email_request_updates' => true]
        );
    }
}