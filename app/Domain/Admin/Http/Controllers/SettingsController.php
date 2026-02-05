<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'title'       => 'Configuracoes',
            'breadcrumbs' => [
                ['label' => 'Configuracoes'],
            ],
        ]);
    }
}
