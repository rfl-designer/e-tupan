<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        return view('admin.activity-logs.index', [
            'title'       => 'Log de Atividades',
            'breadcrumbs' => [
                ['label' => 'Logs de Atividades'],
            ],
        ]);
    }
}
