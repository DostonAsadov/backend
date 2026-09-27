<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id'    => Category::factory(),
            'sku'            => fake()->unique()->bothify('EG-####'),
            'name_ru'        => $name,
            'name_uz'        => $name,
            'description_ru' => fake()->sentence(),
            'description_uz' => fake()->sentence(),
            'price'          => fake()->numberBetween(50, 5000) * 1000,
            'stock'          => fake()->numberBetween(1, 20),
            'images'         => [],
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
