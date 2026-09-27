<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrderIndexRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(AdminOrderIndexRequest $request): AnonymousResourceCollection
    {
        return AdminOrderResource::collection($this->orderService->adminList($request->validated()));
    }

    public function show(Order $order): AdminOrderResource
    {
        return new AdminOrderResource($order->load(['customer', 'items']));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): AdminOrderResource
    {
        $status = OrderStatus::from($request->validated('status'));

        return new AdminOrderResource($this->orderService->changeStatus($order, $status));
    }
}
