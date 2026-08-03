<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['file_name','file_path','file_type','file_size','uploaded_by','related_type','related_id','meta'];
    protected $casts = ['meta' => 'array','file_size' => 'integer'];

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    // polymorphic-like helper (related_type+related_id)
    public function related()
    {
        // you can implement a polymorphic relation if you prefer using morphs
        return null;
    }
}
