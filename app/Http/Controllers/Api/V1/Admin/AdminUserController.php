<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Http\Resources\Admin\StaffResource;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $adminUserService) {}

    public function index(): AnonymousResourceCollection
    {
        return StaffResource::collection($this->adminUserService->list());
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $user = $this->adminUserService->create($request->validated());

        return (new StaffResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateAdminUserRequest $request, User $user): StaffResource
    {
        $actor = auth()->guard('api')->user();

        return new StaffResource($this->adminUserService->update($user, $actor, $request->validated()));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->adminUserService->deactivate($user, auth()->guard('api')->user());

        return response()->json(['message' => 'Сотрудник деактивирован']);
    }
}
