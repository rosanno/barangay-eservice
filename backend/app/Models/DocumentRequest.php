<?php

namespace App\Models;

use App\Enums\DocumentRequestStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tracking_number',
        'user_id',
        'document_type_id',
        'purpose',
        'details',
        'status',
        'fee',
        'payment_status',
        'remarks',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'ready_at',
        'released_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'status' => DocumentRequestStatus::class,
            'payment_status' => PaymentStatus::class,
            'fee' => 'decimal:2',
            'processed_at' => 'datetime',
            'ready_at' => 'datetime',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentRequest $request) {
            $request->uuid ??= (string) \Illuminate\Support\Str::uuid();
            $request->tracking_number ??= static::generateTrackingNumber();
        });
    }

    public static function generateTrackingNumber(): string
    {
        $year = now()->format('Y');

        do {
            $candidate = sprintf('BRGY-%s-%06d', $year, random_int(1, 999999));
        } while (static::where('tracking_number', $candidate)->exists());

        return $candidate;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentRequestAttachment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
