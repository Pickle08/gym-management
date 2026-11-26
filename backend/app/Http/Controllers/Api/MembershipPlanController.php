<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\PlanRequest;
use App\Http\Resources\MembershipPlanResource;
use App\Models\MembershipPlan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $plans = MembershipPlan::query()
            ->when($request->boolean('active_only'), fn($q) => $q->where('is_active', true))
            ->orderBy('duration_days')
            ->get();

        return $this->success(MembershipPlanResource::collection($plans)->resolve());
    }

    public function store(PlanRequest $request): JsonResponse
    {
        $plan = MembershipPlan::create($request->validated());

        return $this->success(new MembershipPlanResource($plan), 'Plan berhasil dibuat.', 201);
    }

    public function update(PlanRequest $request, MembershipPlan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return $this->success(new MembershipPlanResource($plan), 'Plan berhasil diperbarui.');
    }

    public function destroy(MembershipPlan $plan): JsonResponse
    {
        if ($plan->memberships()->exists()) {
            return $this->error('Plan sudah dipakai membership. Nonaktifkan saja (is_active = false).', 422);
        }

        $plan->delete();

        return $this->success(null, 'Plan berhasil dihapus.');
    }
}
