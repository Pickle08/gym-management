<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function create(Member $member, MembershipPlan $plan): Membership
    {
        if (!$plan->is_active) {
            throw new BusinessRuleException('Plan tidak aktif.');
        }

        return DB::transaction(function () use ($member, $plan) {
            // lock baris member supaya dua request bersamaan nggak lolos cek "pending"
            Member::whereKey($member->id)->lockForUpdate()->first();

            if ($member->memberships()->where('status', Membership::STATUS_PENDING)->exists()) {
                throw new BusinessRuleException('Member masih punya membership yang menunggu pembayaran.');
            }

            [$start, $end] = $this->period($member, $plan->duration_days);

            return $member->memberships()->create([
                'membership_plan_id' => $plan->id,
                'start_date' => $start,
                'end_date' => $end,
                'price' => $plan->price, // snapshot harga
                'status' => Membership::STATUS_PENDING,
            ]);
        });
    }

    public function recordPayment(Membership $membership, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($membership, $data, $user) {
            $membership = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if ($membership->status !== Membership::STATUS_PENDING) {
                throw new BusinessRuleException('Membership ini tidak menunggu pembayaran.');
            }

            if ((int) $data['amount'] !== (int) $membership->price) {
                throw new BusinessRuleException(
                    'Nominal harus sama dengan harga membership (Rp ' . number_format($membership->price, 0, ',', '.') . ').'
                );
            }

            // durasi diambil dari record pending itu sendiri, bukan dari plan,
            // supaya edit plan setelah membership dibuat nggak ngubah durasinya
            $days = (int) $membership->start_date->diffInDays($membership->end_date) + 1;
            [$start, $end] = $this->period($membership->member, $days, $membership->id);

            $membership->update([
                'start_date' => $start,
                'end_date' => $end,
                'status' => Membership::STATUS_ACTIVE,
            ]);

            return $membership->payments()->create([
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'received_by' => $user->id,
            ]);
        });
    }

    public function cancel(Membership $membership): Membership
    {
        return DB::transaction(function () use ($membership) {
            $membership = Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();

            if ($membership->status !== Membership::STATUS_PENDING) {
                throw new BusinessRuleException('Hanya membership yang belum dibayar yang bisa dibatalkan.');
            }

            $membership->update(['status' => Membership::STATUS_CANCELLED]);

            return $membership;
        });
    }

    /**
     * Start = hari ini, atau (end_date aktif terakhir + 1 hari) kalau member masih aktif.
     * End inklusif: start + days - 1.
     */
    private function period(Member $member, int $days, ?int $excludeId = null): array
    {
        $today = today();

        $lastEnd = $member->memberships()
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', $today)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->max('end_date');

        $start = $lastEnd ? Carbon::parse($lastEnd)->addDay()->startOfDay() : $today->copy();
        $end = $start->copy()->addDays($days - 1);

        return [$start, $end];
    }
}
