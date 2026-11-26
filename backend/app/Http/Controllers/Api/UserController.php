<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:admin,staff,trainer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $users = User::query()
            ->when($request->q, fn($query, $q) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            }))
            ->when($request->role, fn($query, $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => UserResource::collection($users)->resolve(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated()); // password di-hash oleh cast

        return $this->success(new UserResource($user), 'User berhasil dibuat.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        $isSelf = $request->user()->id === $user->id;

        if (
            $isSelf && (
                (isset($data['role']) && $data['role'] !== $user->role) ||
                (isset($data['is_active']) && !$data['is_active'])
            )
        ) {
            throw new BusinessRuleException('Anda tidak bisa mengubah role atau menonaktifkan akun sendiri.');
        }

        DB::transaction(function () use ($user, $data) {
            // lock semua admin supaya dua request bersamaan nggak sama-sama lolos cek "admin terakhir"
            $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->lockForUpdate()->pluck('id');

            $losesAdmin = $user->role === User::ROLE_ADMIN && $user->is_active && (
                (isset($data['role']) && $data['role'] !== User::ROLE_ADMIN) ||
                (isset($data['is_active']) && !$data['is_active'])
            );

            if ($losesAdmin && $admins->count() <= 1) {
                throw new BusinessRuleException('Harus ada minimal satu admin aktif.');
            }

            $revoke = isset($data['password'])
                || (isset($data['is_active']) && !$data['is_active'])
                || (isset($data['role']) && $data['role'] !== $user->role);

            $user->update($data);

            if ($revoke) {
                $user->tokens()->delete(); // paksa login ulang
            }
        });

        return $this->success(new UserResource($user->refresh()), 'User berhasil diperbarui.');
    }
}
