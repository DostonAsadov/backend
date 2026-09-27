<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';       // создан, ждёт оплаты / обработки
    case Paid = 'paid';             // оплачен онлайн (только по вебхуку Click/Payme)
    case Processing = 'processing'; // собирается
    case Shipped = 'shipped';       // передан в доставку
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
