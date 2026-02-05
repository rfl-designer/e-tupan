<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class CartController extends Controller
{
    /**
     * Display the cart page.
     */
    public function index(): View
    {
        return view('storefront.cart.index');
    }
}
