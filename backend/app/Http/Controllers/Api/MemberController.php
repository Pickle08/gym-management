<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'membership' => ['nullable', 'in:active,inactive'],
        ]);

        $members = Member::query()
            ->with('currentMembership')
            ->visibleTo($request->user())
            ->when($request->q, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('member_code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($request->membership === 'active', fn($query) => $query->whereHas('memberships', fn($m) => $m->active()))
            ->when($request->membership === 'inactive', fn($query) => $query->whereDoesntHave('memberships', fn($m) => $m->active()))
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => MemberResource::collection($members)->resolve(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $member = Member::create($request->validated());

        return $this->success(new MemberResource($member), 'Member berhasil dibuat.', 201);
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        abort_unless(Member::visibleTo($request->user())->whereKey($member->id)->exists(), 404);

        $member->load('currentMembership');

        return $this->success(new MemberResource($member));
    }

    public function update(UpdateMemberRequest $request, Member $member): JsonResponse
    {
        $member->update($request->validated());
        $member->load('currentMembership');

        return $this->success(new MemberResource($member), 'Member berhasil diperbarui.');
    }

    public function destroy(Member $member): JsonResponse
    {
        if ($member->memberships()->active()->exists()) {
            return $this->error('Member masih punya membership aktif, tidak bisa dihapus.', 422);
        }

        $member->delete(); // soft delete

        return $this->success(null, 'Member berhasil dihapus.');
    }

    // Payload buat di-render jadi QR di frontend.
    public function qr(Member $member): JsonResponse
    {
        return $this->success([
            'member_code' => $member->member_code,
            'name' => $member->name,
            'qr_token' => $member->qr_token,
        ]);
    }
}
