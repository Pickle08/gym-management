<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function summary(Request $request): JsonResponse
    {
        $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:60']]);

        $days = $request->integer('days', 7);
        $today = today();
        $monthStart = $today->copy()->startOfMonth();

        $revenue = null;
        if ($request->user()->hasRole('admin')) {
            $revenue = (int) Payment::whereBetween('paid_at', [$monthStart, $today->copy()->endOfDay()])->sum('amount');
        }

        return $this->success([
            'today_checkins' => Attendance::whereDate('attendance_date', $today)->count(),
            'active_members' => Membership::active()->distinct()->count('member_id'),
            'new_members_this_month' => Member::where('created_at', '>=', $monthStart)->count(),
            'pending_payments' => Membership::where('status', Membership::STATUS_PENDING)->count(),
            'expiring_soon' => Membership::expiringWithin($days)->count(),
            'expiring_days' => $days,
            'revenue_this_month' => $revenue,
        ]);
    }

    public function expiring(Request $request): JsonResponse
    {
        $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:60']]);

        $days = $request->integer('days', 7);

        $items = Membership::expiringWithin($days)
            ->with(['member', 'plan'])
            ->orderBy('end_date')
            ->get()
            ->map(fn(Membership $m) => [
                'membership_id' => $m->id,
                'member' => [
                    'id' => $m->member->id,
                    'member_code' => $m->member->member_code,
                    'name' => $m->member->name,
                    'phone' => $m->member->phone,
                ],
                'plan' => $m->plan->name,
                'end_date' => $m->end_date->toDateString(),
                'days_left' => (int) today()->diffInDays($m->end_date),
            ]);

        return $this->success($items);
    }
}
