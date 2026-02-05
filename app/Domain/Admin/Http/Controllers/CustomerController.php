<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        return view('admin.customers.index');
    }

    public function show(User $customer): View
    {
        return view('admin.customers.show', [
            'customer' => $customer,
        ]);
    }
}
