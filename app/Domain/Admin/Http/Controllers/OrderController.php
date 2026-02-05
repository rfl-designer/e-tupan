<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Domain\Checkout\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        return view('admin.orders.index');
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }
}
