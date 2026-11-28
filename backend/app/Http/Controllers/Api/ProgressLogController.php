<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coaching\ProgressLogRequest;
use App\Http\Resources\ProgressLogResource;
use App\Models\ProgressLog;
use App\Models\TrainingProgram;
use App\Services\ProgressService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressLogController extends Controller
{
    use ApiResponse;

    public function __construct(private ProgressService $service)
    {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        // di luar scope trainer = 404, bukan 403
        $program = TrainingProgram::visibleTo($request->user())->with('coachingRequest')->findOrFail($id);

        $logs = $program->progressLogs()->with('recorder')->orderBy('log_date')->get();

        return $this->success([
            'items' => ProgressLogResource::collection($logs)->resolve(),
            'summary' => $this->service->summary($program, $logs),
        ]);
    }

    public function store(ProgressLogRequest $request, int $id): JsonResponse
    {
        $program = TrainingProgram::visibleTo($request->user())->findOrFail($id);

        $log = $this->service->create($program, $request->validated(), $request->user());
        $log->load('recorder');

        return $this->success(new ProgressLogResource($log), 'Pencatatan disimpan.', 201);
    }

    public function update(ProgressLogRequest $request, int $id): JsonResponse
    {
        $log = ProgressLog::visibleTo($request->user())->with('program')->findOrFail($id);

        $log = $this->service->update($log, $request->validated());
        $log->load('recorder');

        return $this->success(new ProgressLogResource($log), 'Pencatatan diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $log = ProgressLog::visibleTo($request->user())->with('program')->findOrFail($id);

        $this->service->delete($log);

        return $this->success(null, 'Pencatatan dihapus.');
    }
}
