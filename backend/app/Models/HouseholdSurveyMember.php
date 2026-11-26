<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdSurveyMember extends Model
{
    protected $fillable = [
        'client_key',
        'resident_id',
        'is_new_resident',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'relationship',
        'sex',
        'birthdate',
        'civil_status',
        'education',
        'employment_status',
        'occupation',
        'is_pwd',
        'is_registered_voter',
        'contact_number',
    ];

    protected $casts = [
        'birthdate' => 'date:Y-m-d',
        'is_new_resident' => 'boolean',
        'is_pwd' => 'boolean',
        'is_registered_voter' => 'boolean',
    ];
}