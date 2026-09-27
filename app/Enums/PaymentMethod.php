<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Click = 'click';
    case Payme = 'payme';
    case Balance = 'balance';
    case Cash = 'cash';
}
