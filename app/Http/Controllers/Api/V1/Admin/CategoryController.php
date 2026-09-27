<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection($this->categoryService->tree());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return (new AdminCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category): AdminCategoryResource
    {
        return new AdminCategoryResource($category->load('children'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): AdminCategoryResource
    {
        return new AdminCategoryResource($this->categoryService->update($category, $request->validated()));
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return response()->json(['message' => 'Категория удалена']);
    }
}
