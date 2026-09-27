<?php

namespace App\Services\Catalog;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    private const DEFAULT_PER_PAGE = 20;

    public const MAX_IMAGES = 10;

    private const IMAGE_DIR = 'products';

    /**
     * Список активных товаров с фильтрами: category (slug), min_price, max_price, sort.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Product::active()->with('category');

        if (!empty($filters['category'])) {
            // товары самой категории и её подкатегорий
            $category = Category::where('slug', $filters['category'])->firstOrFail();
            $categoryIds = $category->children()->pluck('id')->push($category->id);
            $query->whereIn('category_id', $categoryIds);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->paginate($filters['per_page'] ?? self::DEFAULT_PER_PAGE);
    }

    /**
     * Поиск активных товаров по названию (RU/UZ) и SKU.
     */
    public function search(string $term, ?int $perPage = null): LengthAwarePaginator
    {
        $query = Product::active()->with('category')->latest('id');
        $this->applySearch($query, $term);

        return $query->paginate($perPage ?? self::DEFAULT_PER_PAGE);
    }

    /**
     * Карточка активного товара по slug (404, если не найден или скрыт).
     */
    public function findBySlug(string $slug): Product
    {
        return Product::active()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Список для админки: все товары, включая скрытые. Фильтры: q, category_id, is_active.
     */
    public function adminList(array $filters): LengthAwarePaginator
    {
        $query = Product::with('category')->latest('id');

        if (!empty($filters['q'])) {
            $this->applySearch($query, $filters['q']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->paginate($filters['per_page'] ?? self::DEFAULT_PER_PAGE);
    }

    public function create(array $data): Product
    {
        return Product::create($data)->load('category');
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->load('category');
    }

    public function delete(Product $product): void
    {
        Storage::disk('public')->delete($product->images ?? []);
        $product->delete();
    }

    /**
     * Добавляет фото к товару (в конец списка). Первое фото — главное.
     *
     * @param  UploadedFile[]  $files
     */
    public function addImages(Product $product, array $files): Product
    {
        $images = $product->images ?? [];

        if (count($images) + count($files) > self::MAX_IMAGES) {
            throw new BusinessRuleException('У товара может быть не больше ' . self::MAX_IMAGES . ' фото.');
        }

        foreach ($files as $file) {
            $images[] = $file->store(self::IMAGE_DIR, 'public');
        }

        $product->update(['images' => $images]);

        return $product->load('category');
    }

    public function removeImage(Product $product, string $path): Product
    {
        $images = $product->images ?? [];

        if (!in_array($path, $images, true)) {
            throw new BusinessRuleException('У товара нет такого фото.', 404);
        }

        Storage::disk('public')->delete($path);
        $product->update(['images' => array_values(array_diff($images, [$path]))]);

        return $product->load('category');
    }

    // поиск по названию (RU/UZ) и SKU
    private function applySearch(Builder $query, string $term): void
    {
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('name_ru', 'like', $like)
                ->orWhere('name_uz', 'like', $like)
                ->orWhere('sku', 'like', $like);
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc'  => $query->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('price')->orderBy('id'),
            default      => $query->latest('id'),
        };
    }
}
