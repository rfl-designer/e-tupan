<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Http\Controllers;

use App\Domain\Checkout\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the order success page.
     */
    public function success(Request $request, Order $order): View
    {
        // Check if user can access this order
        $this->authorize('view', $order);

        $isGuest = $order->isGuest();

        return view('storefront.checkout.success', [
            'order'   => $order,
            'isGuest' => $isGuest,
        ]);
    }
}
