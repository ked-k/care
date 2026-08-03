<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'file_name', 'file_path', 'file_type', 'file_size',
        'uploaded_by', 'related_type', 'related_id', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * related_type/related_id already follow Eloquent's default morph column
     * naming for a relation called "related", so no custom column mapping needed.
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
