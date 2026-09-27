<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'status',
        'total',
        'delivery_address',
        'payment_method',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'status'           => OrderStatus::class,
            'total'            => 'decimal:2',
            'delivery_address' => 'array',
            'payment_method'   => PaymentMethod::class,
            'payment_status'   => PaymentStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
