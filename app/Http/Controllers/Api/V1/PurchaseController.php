<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\V1\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PurchaseController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    // إنشاء حجز مؤقت
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createTemporaryBooking(
            $request->validated(), 
            $request->user()->id
        );

        return response()->json([
            'message' => 'تم إنشاء الحجز المؤقت بنجاح، بانتظار تأكيد الدفع.',
            'data' => new BookingResource($booking)
        ], Response::HTTP_CREATED);
    }

    // تأكيد الدفع (يعتبر العملية ناجحة فورياً ويطلق طوابير الخلفية)
    public function pay(int $id): JsonResponse
    {
        $booking = $this->bookingService->confirmPayment($id);

        return response()->json([
            'message' => 'تم الدفع وتأكيد الشراء بنجاح، يتم تجهيز تذكرتك الآن.',
            'data' => new BookingResource($booking)
        ], Response::HTTP_OK);
    }
}