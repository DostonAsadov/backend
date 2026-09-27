<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Нарушение бизнес-правила в сервисе (например, удаление категории с товарами).
 * Сервис не знает про HTTP — исключение само превращается в JSON-ответ.
 */
class BusinessRuleException extends Exception
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
