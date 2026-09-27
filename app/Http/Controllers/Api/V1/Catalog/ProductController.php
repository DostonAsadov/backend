<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductIndexRequest;
use App\Http\Requests\Catalog\ProductSearchRequest;
use App\Http\Resources\ProductResource;
use App\Services\Catalog\ProductService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->productService->list($request->validated()));
    }

    public function search(ProductSearchRequest $request): AnonymousResourceCollection
    {
        $products = $this->productService->search(
            $request->validated('q'),
            $request->validated('per_page'),
        );

        return ProductResource::collection($products);
    }

    public function show(string $slug): ProductResource
    {
        return new ProductResource($this->productService->findBySlug($slug));
    }
}
