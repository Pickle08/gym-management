<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coaching\UpdateNutritionRequest;
use App\Http\Requests\Coaching\UpdateProgramStatusRequest;
use App\Http\Resources\TrainingProgramResource;
use App\Models\TrainingProgram;
use App\Services\CoachingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    use ApiResponse;

    private const WITH = ['member', 'trainer', 'coachingRequest.member'];

    public function __construct(private CoachingService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'current' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // trainer melihat semua program miliknya (termasuk yang sudah selesai) sebagai riwayat
        $programs = TrainingProgram::query()
            ->visibleTo($request->user())
            ->with(self::WITH)
            ->when($request->boolean('current'), fn($q) => $q->current())
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => TrainingProgramResource::collection($programs)->resolve(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'last_page' => $programs->lastPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $program = TrainingProgram::visibleTo($request->user())->with(self::WITH)->findOrFail($id);

        return $this->success(new TrainingProgramResource($program));
    }

    public function updateNutrition(UpdateNutritionRequest $request, int $id): JsonResponse
    {
        $program = TrainingProgram::visibleTo($request->user())->findOrFail($id);

        $program = $this->service->updateNutrition($program, $request->validated());
        $program->load(self::WITH);

        return $this->success(new TrainingProgramResource($program), 'Rencana nutrisi disimpan.');
    }

    public function updateStatus(UpdateProgramStatusRequest $request, int $id): JsonResponse
    {
        $program = TrainingProgram::visibleTo($request->user())->findOrFail($id);

        $program = $this->service->closeProgram($program, $request->validated('status'));
        $program->load(self::WITH);

        return $this->success(new TrainingProgramResource($program), 'Status program diperbarui.');
    }
}
