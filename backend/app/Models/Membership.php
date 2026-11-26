<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Membership extends Model
{
    public const STATUS_PENDING = 'pending_payment';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'member_id',
        'membership_plan_id',
        'start_date',
        'end_date',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    // Aktif = status active DAN hari ini di dalam rentang tanggal.
    // "Expired" nggak disimpan, murni hasil perbandingan tanggal.
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function getStateAttribute(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'cancelled';
        }
        if ($this->status === self::STATUS_PENDING) {
            return 'pending_payment';
        }

        $today = today();

        if ($this->end_date->lt($today)) {
            return 'expired';
        }
        if ($this->start_date->gt($today)) {
            return 'upcoming';
        }

        return 'active';
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->active()
            ->whereDate('end_date', '<=', today()->addDays($days)->toDateString())
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('memberships as renewal')
                    ->whereColumn('renewal.member_id', 'memberships.member_id')
                    ->where('renewal.status', self::STATUS_ACTIVE)
                    ->whereColumn('renewal.end_date', '>', 'memberships.end_date');
            });
    }
}
