<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class AttendanceService
{
    public function checkIn(Member $member, User $user): Attendance
    {
        $membership = $member->memberships()->active()->latest('end_date')->first();

        if (!$membership) {
            throw new BusinessRuleException($this->rejectionReason($member));
        }

        $today = today();

        $existing = Attendance::where('member_id', $member->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing) {
            throw new BusinessRuleException(
                'Member sudah check-in hari ini pukul ' . $existing->checked_in_at->format('H:i') . '.'
            );
        }

        try {
            return Attendance::create([
                'member_id' => $member->id,
                'membership_id' => $membership->id,
                'attendance_date' => $today,
                'checked_in_at' => now(),
                'checked_in_by' => $user->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // dua request lolos cek di atas bersamaan, unique(member_id, attendance_date) yang menang
            throw new BusinessRuleException('Member sudah check-in hari ini.');
        }
    }

    private function rejectionReason(Member $member): string
    {
        $latest = $member->memberships()
            ->where('status', Membership::STATUS_ACTIVE)
            ->latest('end_date')
            ->first();

        if ($latest) {
            if ($latest->end_date->lt(today())) {
                return 'Membership expired pada ' . $latest->end_date->format('d M Y') . '.';
            }
            if ($latest->start_date->gt(today())) {
                return 'Membership baru dimulai pada ' . $latest->start_date->format('d M Y') . '.';
            }
        }

        if ($member->memberships()->where('status', Membership::STATUS_PENDING)->exists()) {
            return 'Membership belum dibayar.';
        }

        return 'Member tidak punya membership aktif.';
    }
}
