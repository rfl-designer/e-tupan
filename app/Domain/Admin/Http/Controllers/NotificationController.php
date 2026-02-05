<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications.index', [
            'title'       => 'Notificacoes',
            'breadcrumbs' => [
                ['label' => 'Notificacoes'],
            ],
        ]);
    }
}
