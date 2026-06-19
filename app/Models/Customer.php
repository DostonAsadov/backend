<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'name',
        'phone',
        'password',
        'telegram_id',
        'telegram_username',
        'locale',
        'balance',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance'  => 'decimal:2',
        ];
    }

    // JWT обязательные методы
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'type' => 'customer', // отличаем от admin токена
        ];
    }
}
