<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangaySetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'contact_number',
        'email',
        'office_hours',
    ];

    /**
     * This table only ever has one row. Everything in the app that needs
     * barangay info should go through this rather than querying the table
     * directly, so the "singleton" assumption lives in one place.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['name' => 'Barangay San Roque']);
    }
}