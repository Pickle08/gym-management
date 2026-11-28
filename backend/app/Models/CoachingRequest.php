<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoachingRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const GOALS = ['bulking', 'cutting', 'recomposition', 'endurance'];

    protected $fillable = [
        'member_id',
        'trainer_id',
        'goal_type',
        'height_cm',
        'initial_weight_kg',
        'target_weight_kg',
        'activity_level',
        'medical_notes',
        'preferred_days',
        'status',
        'reject_reason',
        'requested_by',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_weight_kg' => 'float',
            'target_weight_kg' => 'float',
            'preferred_days' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    // Trainer hanya melihat pengajuan yang ditujukan ke dirinya
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole(User::ROLE_TRAINER)
            ? $query->where('trainer_id', $user->id)
            : $query;
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function program(): HasOne
    {
        return $this->hasOne(TrainingProgram::class);
    }
}
