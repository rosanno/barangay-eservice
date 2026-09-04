<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_request_id',
        'label',
        'original_name',
        'path',
        'mime_type',
        'size',
    ];

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('private')->temporaryUrl(
            $this->path,
            now()->addMinutes(15)
        );
    }
}
