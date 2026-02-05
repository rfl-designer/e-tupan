<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Exceptions;

use Exception;

class ProductNotAvailableException extends Exception
{
    public function __construct(
        public readonly string $productName,
    ) {
        parent::__construct("O produto '{$productName}' nao esta disponivel para compra.");
    }
}
