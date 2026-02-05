<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Domain\Admin\Http\Requests\{AdminForgotPasswordRequest, AdminResetPasswordRequest};
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Notifications\AdminResetPasswordNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    /**
     * Display the forgot password form.
     */
    public function showForgotForm(): View
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Send a password reset link to the admin.
     */
    public function sendResetLink(AdminForgotPasswordRequest $request): RedirectResponse
    {
        $admin = Admin::where('email', $request->validated('email'))->first();

        if ($admin) {
            $token = Password::broker('admins')->createToken($admin);
            $admin->notify(new AdminResetPasswordNotification($token));
        }

        // Always return success to prevent email enumeration
        return back()->with('status', __('If an account exists, a password reset link has been sent.'));
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the admin's password.
     */
    public function reset(AdminResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password'       => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('admin.login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }
}
