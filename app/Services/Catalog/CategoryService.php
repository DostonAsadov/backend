<?php

namespace App\Services\Catalog;

use App\Exceptions\BusinessRuleException;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Дерево категорий: корневые категории с вложенными подкатегориями (2 уровня).
     */
    public function tree(): Collection
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        // только 2 уровня: категория с подкатегориями не может стать подкатегорией
        if (!empty($data['parent_id']) && $category->children()->exists()) {
            throw new BusinessRuleException('Категория с подкатегориями не может быть вложенной.');
        }

        $category->update($data);

        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw new BusinessRuleException('Нельзя удалить категорию с подкатегориями.', 409);
        }

        if ($category->products()->exists()) {
            throw new BusinessRuleException('Нельзя удалить категорию, в которой есть товары.', 409);
        }

        $category->delete();
    }
}
