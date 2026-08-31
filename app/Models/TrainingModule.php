<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingModule extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['title', 'description', 'url', 'duration_minutes', 'category'];

    protected $casts = [
        'duration_minutes' => 'integer',
    ];

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'module_id');
    }

    public function completedByCount(): int
    {
        return $this->progress()->where('status', 'completed')->count();
    }
}
