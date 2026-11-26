<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponse;

    public function revenue(Request $request): JsonResponse
    {
        $request->validate(['group' => ['nullable', 'in:day,month']]);
        [$from, $to] = $this->range($request);

        $group = $request->input('group', 'day');
        $format = $group === 'month' ? '%Y-%m' : '%Y-%m-%d'; // whitelist internal, bukan input user

        $base = Payment::whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $rows = (clone $base)
            ->selectRaw("DATE_FORMAT(paid_at, '{$format}') as period, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $series = [];
        $start = $group === 'month' ? $from->copy()->startOfMonth() : $from->copy();
        $step = $group === 'month' ? '1 month' : '1 day';

        foreach (CarbonPeriod::create($start, $step, $to) as $d) {
            $key = $d->format($group === 'month' ? 'Y-m' : 'Y-m-d');
            $series[] = [
                'period' => $key,
                'total' => (int) ($rows[$key]->total ?? 0),
                'count' => (int) ($rows[$key]->count ?? 0),
            ];
        }

        $byMethod = (clone $base)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('method')
            ->get()
            ->map(fn($r) => ['method' => $r->method, 'total' => (int) $r->total, 'count' => (int) $r->count]);

        return $this->success([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => $group,
            'total' => (int) $base->sum('amount'),
            'count' => $base->count(),
            'by_method' => $byMethod,
            'series' => $series,
        ]);
    }

    public function attendance(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        $base = Attendance::whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);

        $rows = (clone $base)
            ->selectRaw('attendance_date as period, COUNT(*) as visits')
            ->groupBy('attendance_date')
            ->get()
            ->keyBy('period');

        $series = [];
        foreach (CarbonPeriod::create($from, '1 day', $to) as $d) {
            $key = $d->format('Y-m-d');
            $series[] = ['period' => $key, 'visits' => (int) ($rows[$key]->visits ?? 0)];
        }

        return $this->success([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total_visits' => (clone $base)->count(),
            'unique_members' => (clone $base)->distinct()->count('member_id'),
            'series' => $series,
        ]);
    }

    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->startOfDay() : today();
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : $to->copy()->startOfMonth();

        if ($from->diffInDays($to) > 366) {
            throw new BusinessRuleException('Rentang tanggal maksimal 366 hari.');
        }

        return [$from, $to];
    }
}
