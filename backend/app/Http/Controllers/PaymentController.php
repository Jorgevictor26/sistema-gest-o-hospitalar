<?php

namespace App\Http\Controllers;

use App\DTOs\PaymentDTO;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\VoidPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Attendance;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Attendance $attendance): AnonymousResourceCollection
    {
        return PaymentResource::collection(
            $this->paymentService->list($attendance)
        );
    }

    public function store(StorePaymentRequest $request, Attendance $attendance): JsonResponse
    {
        $payment = $this->paymentService->create(
            $attendance,
            PaymentDTO::fromArray($request->validated()),
            $request->user(),
        );

        return response()->json([
            'payment' => new PaymentResource($payment),
            'balance' => $this->balance($attendance->refresh()),
        ], 201);
    }

    public function void(VoidPaymentRequest $request, Payment $payment): JsonResponse
    {
        $payment = $this->paymentService->void(
            $payment,
            $request->validated('reason'),
            $request->user(),
        );

        return response()->json([
            'payment' => new PaymentResource($payment),
            'balance' => $this->balance($payment->attendance->refresh()),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function balance(Attendance $attendance): array
    {
        return [
            'total_amount' => $attendance->total_amount,
            'amount_paid' => $attendance->amount_paid,
            'pending_amount' => Money::format($attendance->pendingAmount()),
            'payment_status' => $attendance->paymentStatus(),
        ];
    }
}
