<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categoryService) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categoryService->tree());
    }
}
