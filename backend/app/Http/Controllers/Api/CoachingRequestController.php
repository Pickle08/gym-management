<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coaching\RespondRequest;
use App\Http\Requests\Coaching\StoreCoachingRequest;
use App\Http\Resources\CoachingRequestResource;
use App\Models\CoachingRequest;
use App\Services\CoachingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class CoachingRequestController extends Controller
{
    use ApiResponse;

    private const WITH = ['member', 'trainer', 'requester', 'program'];

    public function __construct(private CoachingService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,accepted,rejected,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'member_id' => ['nullable', 'integer'],
        ]);

        $requests = CoachingRequest::query()
            ->visibleTo($request->user())
            ->with(self::WITH)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->member_id, fn($q, $id) => $q->where('member_id', $id))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => CoachingRequestResource::collection($requests)->resolve(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function trainers(): JsonResponse
    {
        $trainers = User::where('role', User::ROLE_TRAINER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->success($trainers);
    }

    public function store(StoreCoachingRequest $request): JsonResponse
    {
        $coachingRequest = $this->service->createRequest($request->validated(), $request->user());
        $coachingRequest->load(self::WITH);

        return $this->success(
            new CoachingRequestResource($coachingRequest),
            'Pengajuan coaching dibuat, menunggu respons trainer.',
            201
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        // di luar scope trainer = 404 (bukan 403), supaya keberadaan data nggak bocor
        $coachingRequest = CoachingRequest::visibleTo($request->user())->with(self::WITH)->findOrFail($id);

        return $this->success(new CoachingRequestResource($coachingRequest));
    }

    public function respond(RespondRequest $request, int $id): JsonResponse
    {
        $coachingRequest = CoachingRequest::visibleTo($request->user())->findOrFail($id);

        $result = $this->service->respond($coachingRequest, $request->validated());
        $result->load(self::WITH);

        $message = $request->validated('action') === 'accept'
            ? 'Pengajuan diterima, program dimulai.'
            : 'Pengajuan ditolak.';

        return $this->success(new CoachingRequestResource($result), $message);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $coachingRequest = CoachingRequest::findOrFail($id);

        $result = $this->service->cancelRequest($coachingRequest);
        $result->load(self::WITH);

        return $this->success(new CoachingRequestResource($result), 'Pengajuan dibatalkan.');
    }
}
