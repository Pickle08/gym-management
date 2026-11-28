<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressLog extends Model
{
    protected $fillable = [
        'training_program_id',
        'log_date',
        'weight_kg',
        'body_fat_pct',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'weight_kg' => 'float',
            'body_fat_pct' => 'float',
        ];
    }

    // Trainer hanya melihat log dari program miliknya
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole(User::ROLE_TRAINER)
            ? $query->whereHas('program', fn($p) => $p->where('trainer_id', $user->id))
            : $query;
    }

    // FK harus disebut eksplisit: nama method `program` akan menebak `program_id`
    public function program(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
