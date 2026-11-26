<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\StoreMembershipRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class MembershipController extends Controller
{
    use ApiResponse;

    public function __construct(private MembershipService $service)
    {
    }

    public function indexByMember(Member $member): JsonResponse
    {
        $memberships = $member->memberships()
            ->with(['plan', 'payments.receiver'])
            ->latest('id')
            ->get();

        return $this->success(MembershipResource::collection($memberships)->resolve());
    }

    public function store(StoreMembershipRequest $request, Member $member): JsonResponse
    {
        $plan = MembershipPlan::findOrFail($request->validated('membership_plan_id'));
        $membership = $this->service->create($member, $plan);
        $membership->load('plan');

        return $this->success(new MembershipResource($membership), 'Membership dibuat, menunggu pembayaran.', 201);
    }

    public function show(Membership $membership): JsonResponse
    {
        $membership->load(['plan', 'payments.receiver']);

        return $this->success(new MembershipResource($membership));
    }

    public function cancel(Membership $membership): JsonResponse
    {
        $membership = $this->service->cancel($membership);
        $membership->load('plan');

        return $this->success(new MembershipResource($membership), 'Membership dibatalkan.');
    }
}
