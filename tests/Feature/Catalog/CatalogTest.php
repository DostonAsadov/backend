<?php

namespace Tests\Feature\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_returned_as_tree_in_requested_locale(): void
    {
        $parent = Category::factory()->create(['name_ru' => 'Электроника', 'name_uz' => 'Elektronika']);
        Category::factory()->create(['name_ru' => 'Дисплеи', 'name_uz' => 'Displeylar', 'parent_id' => $parent->id]);

        $this->getJson('/api/v1/categories', ['Accept-Language' => 'ru'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Электроника')
            ->assertJsonPath('data.0.children.0.name', 'Дисплеи');

        // по умолчанию — узбекский
        $this->getJson('/api/v1/categories')
            ->assertJsonPath('data.0.name', 'Elektronika');
    }

    public function test_product_list_hides_inactive_products(): void
    {
        Product::factory()->count(2)->create();
        Product::factory()->inactive()->create();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'sku', 'slug', 'name', 'description', 'price', 'stock', 'in_stock', 'images', 'category']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_category_filter_includes_subcategories(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        Product::factory()->create(['category_id' => $parent->id]);
        Product::factory()->create(['category_id' => $child->id]);
        Product::factory()->create(); // другая категория

        $this->getJson("/api/v1/products?category={$parent->slug}")->assertJsonCount(2, 'data');
        $this->getJson("/api/v1/products?category={$child->slug}")->assertJsonCount(1, 'data');
    }

    public function test_price_filter_and_sort(): void
    {
        foreach ([100_000, 300_000, 500_000] as $price) {
            Product::factory()->create(['price' => $price]);
        }

        $this->getJson('/api/v1/products?min_price=200000&max_price=600000&sort=price_desc')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.price', '500000.00')
            ->assertJsonPath('data.1.price', '300000.00');
    }

    public function test_invalid_filters_return_422_json(): void
    {
        $this->get('/api/v1/products?min_price=500&max_price=100&sort=random&category=missing')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_price', 'sort', 'category']);
    }

    public function test_search_by_name_and_sku(): void
    {
        Product::factory()->create(['name_ru' => 'Контроллер 48V', 'name_uz' => 'Kontroller 48V', 'sku' => 'CT-30']);
        Product::factory()->create(['name_ru' => 'Дисплей', 'name_uz' => 'Displey', 'sku' => 'DS-1']);
        Product::factory()->inactive()->create(['name_ru' => 'Контроллер старый', 'sku' => 'CT-OLD']);

        // SQLite LIKE регистронезависим только для латиницы; в MySQL (utf8mb4) — и для кириллицы
        $this->getJson('/api/v1/products/search?q=Контроллер')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products/search?q=kontroller')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products/search?q=DS-1')->assertJsonPath('data.0.sku', 'DS-1');
        $this->getJson('/api/v1/products/search')->assertStatus(422);
    }

    public function test_show_product_by_slug(): void
    {
        $product = Product::factory()->outOfStock()->create(['name_ru' => 'Мотор 48V', 'name_uz' => 'Motor 48V']);

        $this->getJson("/api/v1/products/{$product->slug}", ['Accept-Language' => 'ru'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Мотор 48V')
            ->assertJsonPath('data.in_stock', false)
            ->assertJsonPath('data.category.id', $product->category_id);
    }

    public function test_inactive_or_missing_product_returns_404_json(): void
    {
        $hidden = Product::factory()->inactive()->create();

        $this->get("/api/v1/products/{$hidden->slug}")->assertNotFound()->assertJsonStructure(['message']);
        $this->get('/api/v1/products/no-such-product')->assertNotFound()->assertJsonStructure(['message']);
    }
}
