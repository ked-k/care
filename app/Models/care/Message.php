<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['sender_id','receiver_id','message','encrypted','attachment_id','read_at'];
    protected $casts = ['encrypted' => 'boolean','read_at' => 'datetime'];

    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function attachment() { return $this->belongsTo(MediaFile::class, 'attachment_id'); }
}
