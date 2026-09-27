<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class AdminUserService
{
    public function list(): Collection
    {
        return User::orderBy('id')->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, User $actor, array $data): User
    {
        // админ не может сам себя понизить или отключить — иначе можно остаться без админов
        if ($user->is($actor)) {
            if (isset($data['role']) && $data['role'] !== $actor->role->value) {
                throw new BusinessRuleException('Нельзя изменить собственную роль.');
            }
            if (isset($data['is_active']) && !$data['is_active']) {
                throw new BusinessRuleException('Нельзя деактивировать самого себя.');
            }
        }

        $user->update($data);

        return $user;
    }

    public function deactivate(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new BusinessRuleException('Нельзя деактивировать самого себя.');
        }

        $user->update(['is_active' => false]);
    }
}
