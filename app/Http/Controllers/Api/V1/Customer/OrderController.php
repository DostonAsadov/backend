<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Order\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $order = $this->orderService->checkout(auth()->guard('customer')->user(), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function index(): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->customerOrders(auth()->guard('customer')->user()));
    }

    public function show(int $order): OrderResource
    {
        return new OrderResource($this->orderService->customerOrder(auth()->guard('customer')->user(), $order));
    }
}
