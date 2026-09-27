<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function asManager(): static
    {
        return $this->actingAs(User::factory()->create(), 'api');
    }

    public function test_guests_and_customers_cannot_access_admin_catalog(): void
    {
        $this->getJson('/api/v1/admin/products')->assertUnauthorized();

        $customer = Customer::create(['name' => 'Test', 'phone' => '+998901112233', 'password' => 'secret123']);
        $token = auth()->guard('customer')->login($customer);

        $this->getJson('/api/v1/admin/products', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
    }

    public function test_inactive_staff_is_forbidden(): void
    {
        $this->actingAs(User::factory()->inactive()->create(), 'api')
            ->getJson('/api/v1/admin/products')
            ->assertForbidden();
    }

    public function test_manager_can_crud_categories(): void
    {
        $this->asManager();

        $parentId = $this->postJson('/api/v1/admin/categories', ['name_ru' => 'Электроника', 'name_uz' => 'Elektronika'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'elektronika')
            ->json('data.id');

        $childId = $this->postJson('/api/v1/admin/categories', ['name_ru' => 'Дисплеи', 'name_uz' => 'Displeylar', 'parent_id' => $parentId])
            ->assertCreated()
            ->json('data.id');

        // 3-й уровень вложенности запрещён
        $this->postJson('/api/v1/admin/categories', ['name_ru' => 'X', 'name_uz' => 'X', 'parent_id' => $childId])
            ->assertJsonValidationErrors('parent_id');

        $this->putJson("/api/v1/admin/categories/{$childId}", ['name_ru' => 'Экраны'])
            ->assertOk()
            ->assertJsonPath('data.name_ru', 'Экраны')
            ->assertJsonPath('data.slug', 'displei'); // slug не меняется при переименовании

        // нельзя удалить родителя, пока есть подкатегории
        $this->deleteJson("/api/v1/admin/categories/{$parentId}")->assertStatus(409);

        $this->deleteJson("/api/v1/admin/categories/{$childId}")->assertOk();
        $this->deleteJson("/api/v1/admin/categories/{$parentId}")->assertOk();
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->asManager()
            ->deleteJson("/api/v1/admin/categories/{$product->category_id}")
            ->assertStatus(409);
    }

    public function test_manager_can_crud_products(): void
    {
        $category = Category::factory()->create();
        $this->asManager();

        $id = $this->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'sku'         => 'MT-500',
            'name_ru'     => 'Мотор 48V',
            'name_uz'     => 'Motor 48V',
            'price'       => 2300000,
            'stock'       => 5,
            'is_active'   => false,
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'motor-48v')
            ->assertJsonPath('data.is_active', false)
            ->json('data.id');

        // скрытый товар виден в админке, но не в публичном каталоге
        $this->getJson('/api/v1/admin/products?is_active=0')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products')->assertJsonCount(0, 'data');

        $this->postJson('/api/v1/admin/products', ['category_id' => $category->id, 'sku' => 'MT-500', 'name_ru' => 'a', 'name_uz' => 'a', 'price' => 1, 'stock' => 1])
            ->assertJsonValidationErrors('sku');

        $this->putJson("/api/v1/admin/products/{$id}", ['price' => 2500000, 'is_active' => true, 'sku' => 'MT-500'])
            ->assertOk()
            ->assertJsonPath('data.price', '2500000.00');

        $this->getJson('/api/v1/products')->assertJsonCount(1, 'data');

        $this->deleteJson("/api/v1/admin/products/{$id}")->assertOk();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_images_upload_and_delete(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $this->asManager();

        $images = $this->post("/api/v1/admin/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ])->assertOk()
            ->assertJsonCount(2, 'data.images')
            ->json('data.images');

        Storage::disk('public')->assertExists($images[0]['path']);

        // не картинка
        $this->post("/api/v1/admin/products/{$product->id}/images", [
            'images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
        ])->assertJsonValidationErrors('images.0');

        // лимит 10 фото
        $this->post("/api/v1/admin/products/{$product->id}/images", [
            'images' => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg"), range(1, 9)),
        ])->assertStatus(422);

        $this->deleteJson("/api/v1/admin/products/{$product->id}/images", ['path' => $images[0]['path']])
            ->assertOk()
            ->assertJsonCount(1, 'data.images');
        Storage::disk('public')->assertMissing($images[0]['path']);

        $this->deleteJson("/api/v1/admin/products/{$product->id}/images", ['path' => 'products/other.jpg'])
            ->assertNotFound();

        // удаление товара удаляет и файлы
        $this->deleteJson("/api/v1/admin/products/{$product->id}")->assertOk();
        Storage::disk('public')->assertMissing($images[1]['path']);
    }
}
