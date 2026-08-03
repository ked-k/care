<?php

namespace App\Models\care;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TrainingModule extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['title','description','url','duration_minutes','category'];

    public function progress() { return $this->hasMany(TrainingProgress::class, 'module_id'); }
}
