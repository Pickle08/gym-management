<?php

namespace App\Models;

use App\Services\NutritionCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgram extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'coaching_request_id',
        'member_id',
        'trainer_id',
        'start_date',
        'end_date',
        'status',
        'daily_calories',
        'protein_g',
        'carbs_g',
        'fats_g',
        'diet_guidelines',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // Program yang sedang berjalan: status active dan belum lewat end_date
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', today()->toDateString());
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole(User::ROLE_TRAINER)
            ? $query->where('trainer_id', $user->id)
            : $query;
    }

    // Status tampilan, dihitung dari tanggal (expired tidak disimpan)
    public function getStateAttribute(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return $this->status;
        }
        if ($this->end_date->lt(today())) {
            return 'expired';
        }
        if ($this->start_date->gt(today())) {
            return 'upcoming';
        }

        return 'active';
    }

    public function macroCalories(): ?int
    {
        if ($this->protein_g === null || $this->carbs_g === null || $this->fats_g === null) {
            return null;
        }

        return NutritionCalculator::macroCalories($this->protein_g, $this->carbs_g, $this->fats_g);
    }

    public function coachingRequest(): BelongsTo
    {
        return $this->belongsTo(CoachingRequest::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
