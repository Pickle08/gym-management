<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Member;
use App\Services\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(private AttendanceService $service)
    {
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $member = $request->filled('qr_token')
            ? Member::where('qr_token', $request->validated('qr_token'))->first()
            : Member::find($request->validated('member_id'));

        if (!$member) {
            // pesan generik, jangan bocorin apakah token "hampir benar"
            return $this->error('QR tidak valid.', 404);
        }

        $attendance = $this->service->checkIn($member, $request->user());
        $attendance->load(['member', 'membership', 'checkedInBy']);

        return $this->success(new AttendanceResource($attendance), 'Check-in berhasil.', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'member_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Attendance::query()->with(['member', 'membership', 'checkedInBy']);

        $user = $request->user();

        if ($user->hasRole(\App\Models\User::ROLE_TRAINER)) {
            $query->whereHas('member', fn($m) => $m->visibleTo($user));
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->filled('from') || $request->filled('to')) {
            $query->when($request->from, fn($q, $d) => $q->whereDate('attendance_date', '>=', $d))
                ->when($request->to, fn($q, $d) => $q->whereDate('attendance_date', '<=', $d));
        } else {
            // default: hari ini (atau tanggal tertentu lewat ?date=)
            $query->whereDate('attendance_date', $request->input('date', today()->toDateString()));
        }

        $attendances = $query->latest('checked_in_at')->paginate($request->integer('per_page', 20));

        return $this->success([
            'items' => AttendanceResource::collection($attendances)->resolve(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }
}
