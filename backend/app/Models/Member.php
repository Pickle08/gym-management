<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'birth_date', 'gender', 'address'];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            $member->qr_token = Str::random(40);
            $member->member_code = Str::random(20); // placeholder, diganti setelah dapat ID
        });

        static::created(function (Member $member) {
            $member->member_code = 'GYM-' . str_pad((string) $member->id, 5, '0', STR_PAD_LEFT);
            $member->saveQuietly();
        });
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->ofMany(['end_date' => 'max', 'id' => 'max'], fn($q) => $q->active());
    }

    public function activeMembership(): ?Membership
    {
        return $this->memberships()->active()->latest('end_date')->first();
    }
    public function coachingRequests(): HasMany
    {
        return $this->hasMany(CoachingRequest::class);
    }

    public function trainingPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }

    // Trainer hanya boleh melihat member yang sedang dia tangani
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (!$user->hasRole(User::ROLE_TRAINER)) {
            return $query;
        }

        return $query->whereHas(
            'trainingPrograms',
            fn($p) => $p->current()->where('trainer_id', $user->id)
        );
    }
}
