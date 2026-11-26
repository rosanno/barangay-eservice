<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HouseholdSurvey extends Model
{
    protected $fillable = [
        'current_step',
        'household_number',
        'head_name',
        'address',
        'purok',
        'housing_type',
        'ownership_status',
        'housing_condition',
        'water_source',
        'has_electricity',
        'sanitation',
        'extra_answers',
    ];

    protected $casts = [
        'has_electricity' => 'boolean',
        'extra_answers' => 'array',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public const EDITABLE = ['draft', 'needs_revision'];

    /** Single source of truth for dropdown values. Edit freely to match barangay forms. */
    public const OPTIONS = [
        'housing_type' => ['single_detached' => 'Single detached', 'duplex' => 'Duplex', 'apartment' => 'Apartment / condominium', 'makeshift' => 'Makeshift / improvised', 'other' => 'Other'],
        'ownership_status' => ['owned' => 'Owned', 'rented' => 'Rented', 'living_with_relatives' => 'Living with relatives', 'caretaker' => 'Caretaker', 'informal_settler' => 'Informal settler', 'other' => 'Other'],
        'housing_condition' => ['good' => 'Good', 'minor_repair' => 'Needs minor repair', 'major_repair' => 'Needs major repair', 'dilapidated' => 'Dilapidated'],
        'water_source' => ['piped_own' => 'Piped water (own tap)', 'shared_tap' => 'Shared / community tap', 'deep_well' => 'Deep well', 'spring' => 'Spring', 'refilling' => 'Water refilling station', 'other' => 'Other'],
        'sanitation' => ['sealed_own' => 'Water-sealed toilet (own)', 'sealed_shared' => 'Water-sealed toilet (shared)', 'open_pit' => 'Open pit', 'none' => 'None'],
        'relationship' => ['head' => 'Household head', 'spouse' => 'Spouse', 'child' => 'Child', 'parent' => 'Parent', 'sibling' => 'Sibling', 'grandchild' => 'Grandchild', 'other_relative' => 'Other relative', 'non_relative' => 'Non-relative'],
        'sex' => ['male' => 'Male', 'female' => 'Female'],
        'civil_status' => ['single' => 'Single', 'married' => 'Married', 'live_in' => 'Live-in', 'widowed' => 'Widowed', 'separated' => 'Separated'],
        'education' => ['none' => 'No formal education', 'elementary' => 'Elementary', 'high_school' => 'High school', 'vocational' => 'Vocational', 'college' => 'College', 'postgraduate' => 'Postgraduate'],
        'employment_status' => ['employed' => 'Employed', 'self_employed' => 'Self-employed', 'unemployed' => 'Unemployed', 'student' => 'Student', 'retired' => 'Retired', 'homemaker' => 'Homemaker'],
    ];

    /** Barangay-approved extra questions (stored in extra_answers by key). */
    public const EXTRA_QUESTIONS = [
        ['key' => 'is_4ps', 'label' => 'Is the household a 4Ps beneficiary?', 'type' => 'yesno'],
        ['key' => 'has_senior', 'label' => 'Does the household have a senior citizen?', 'type' => 'yesno'],
        ['key' => 'has_pwd', 'label' => 'Does the household have a person with disability?', 'type' => 'yesno'],
        ['key' => 'remarks', 'label' => 'Enumerator remarks', 'type' => 'text'],
    ];

    public static function values(string $key): array
    {
        return array_keys(self::OPTIONS[$key]);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE, true);
    }

    public function campaign()
    {
        return $this->belongsTo(CensusCampaign::class, 'census_campaign_id');
    }
    public function target()
    {
        return $this->belongsTo(CensusCampaignHousehold::class, 'census_campaign_household_id');
    }
    public function enumerator()
    {
        return $this->belongsTo(User::class, 'enumerator_id');
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    public function members()
    {
        return $this->hasMany(HouseholdSurveyMember::class)->orderBy('id');
    }
}