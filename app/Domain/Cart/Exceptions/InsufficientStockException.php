<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly string $productName,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
    ) {
        $message = "Estoque insuficiente para '{$productName}'. Solicitado: {$requestedQuantity}, Disponivel: {$availableQuantity}";
        parent::__construct($message);
    }
}
