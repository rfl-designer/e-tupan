<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Domain\Admin\Http\Requests\{StoreAdminRequest, UpdateAdminRequest};
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Notifications\AdminInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display a listing of administrators.
     */
    public function index(): View
    {
        $admins = Admin::query()
            ->orderBy('name')
            ->get();

        return view('admin.administrators.index', compact('admins'));
    }

    /**
     * Show the form for creating a new administrator.
     */
    public function create(): View
    {
        return view('admin.administrators.create');
    }

    /**
     * Store a newly created administrator in storage.
     */
    public function store(StoreAdminRequest $request): RedirectResponse
    {
        $password = Str::random(16);

        $admin = Admin::create([
            'name'      => $request->validated('name'),
            'email'     => $request->validated('email'),
            'password'  => $password,
            'role'      => $request->validated('role'),
            'is_active' => true,
        ]);

        // Send invitation email with password reset link
        /** @var PasswordBroker $broker */
        $broker = Password::broker('admins');
        $token  = $broker->createToken($admin);
        $admin->notify(new AdminInvitation($token));

        return redirect()->route('admin.administrators.index')
            ->with('success', 'Administrador criado. Um email de convite foi enviado.');
    }

    /**
     * Show the form for editing the specified administrator.
     */
    public function edit(Admin $administrator): View
    {
        return view('admin.administrators.edit', ['admin' => $administrator]);
    }

    /**
     * Update the specified administrator in storage.
     */
    public function update(UpdateAdminRequest $request, Admin $administrator): RedirectResponse
    {
        // Check if trying to demote last master
        if ($administrator->isMaster() && $request->validated('role') === 'operator') {
            $masterCount = Admin::query()->where('role', 'master')->count();

            if ($masterCount <= 1) {
                return back()
                    ->withInput()
                    ->withErrors(['role' => 'Não é possível rebaixar o último administrador master.']);
            }
        }

        $administrator->update($request->validated());

        return redirect()->route('admin.administrators.index')
            ->with('success', 'Administrador atualizado com sucesso.');
    }

    /**
     * Remove the specified administrator from storage.
     */
    public function destroy(Admin $administrator): RedirectResponse
    {
        // Cannot delete self
        if ($administrator->id === auth('admin')->id()) {
            return back()->withErrors(['error' => 'Você não pode excluir sua própria conta.']);
        }

        // Cannot delete last master
        if ($administrator->isMaster()) {
            $masterCount = Admin::query()->where('role', 'master')->count();

            if ($masterCount <= 1) {
                return back()->withErrors(['error' => 'Não é possível excluir o último administrador master.']);
            }
        }

        $administrator->delete(); // Soft delete

        return redirect()->route('admin.administrators.index')
            ->with('success', 'Administrador excluído com sucesso.');
    }
}
