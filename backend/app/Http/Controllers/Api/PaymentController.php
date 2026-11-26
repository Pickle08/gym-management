<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Membership;
use App\Services\MembershipService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private MembershipService $service)
    {
    }

    public function store(StorePaymentRequest $request, Membership $membership): JsonResponse
    {
        $this->service->recordPayment($membership, $request->validated(), $request->user());

        $membership->refresh()->load(['plan', 'payments.receiver']);

        return $this->success(new MembershipResource($membership), 'Pembayaran tercatat, membership aktif.', 201);
    }
}
