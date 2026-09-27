<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProductIndexRequest;
use App\Http\Requests\Admin\DeleteProductImageRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Requests\Admin\UploadProductImagesRequest;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(AdminProductIndexRequest $request): AnonymousResourceCollection
    {
        return AdminProductResource::collection($this->productService->adminList($request->validated()));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return (new AdminProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): AdminProductResource
    {
        return new AdminProductResource($product->load('category'));
    }

    public function update(UpdateProductRequest $request, Product $product): AdminProductResource
    {
        return new AdminProductResource($this->productService->update($product, $request->validated()));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(['message' => 'Товар удалён']);
    }

    public function uploadImages(UploadProductImagesRequest $request, Product $product): AdminProductResource
    {
        return new AdminProductResource($this->productService->addImages($product, $request->file('images')));
    }

    public function deleteImage(DeleteProductImageRequest $request, Product $product): AdminProductResource
    {
        return new AdminProductResource($this->productService->removeImage($product, $request->validated('path')));
    }
}
