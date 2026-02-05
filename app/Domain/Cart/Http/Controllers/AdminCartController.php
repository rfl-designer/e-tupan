<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminCartController extends Controller
{
    /**
     * Display the abandoned carts listing.
     */
    public function abandoned(): View
    {
        return view('admin.carts.abandoned');
    }
}
