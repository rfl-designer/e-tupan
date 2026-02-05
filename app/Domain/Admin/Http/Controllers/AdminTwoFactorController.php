<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Domain\Admin\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorController extends Controller
{
    /**
     * Display the two-factor authentication setup page.
     */
    public function showSetup(): View|RedirectResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        // If 2FA is already configured and confirmed, redirect to dashboard
        if ($admin->two_factor_confirmed_at) {
            return redirect()->route('admin.dashboard');
        }

        // Enable 2FA if not already enabled
        if (!$admin->two_factor_secret) {
            app(EnableTwoFactorAuthentication::class)($admin);
            $admin->refresh();
        }

        return view('admin.auth.two-factor-setup', [
            'qrCodeSvg'     => $admin->twoFactorQrCodeSvg(),
            'recoveryCodes' => json_decode(decrypt($admin->two_factor_recovery_codes), true),
        ]);
    }

    /**
     * Confirm the two-factor authentication setup.
     */
    public function confirmSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var Admin $admin */
        $admin     = auth('admin')->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey(
            decrypt($admin->two_factor_secret),
            $request->string('code')->toString(),
        );

        if (!$valid) {
            return back()->withErrors(['code' => __('Invalid code. Please try again.')]);
        }

        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();
        session(['admin_two_factor_confirmed' => true]);

        return redirect()->route('admin.dashboard')
            ->with('success', __('Two-factor authentication has been configured successfully!'));
    }

    /**
     * Display the two-factor authentication challenge page.
     */
    public function showChallenge(): View
    {
        return view('admin.auth.two-factor-challenge');
    }

    /**
     * Verify the two-factor authentication challenge.
     */
    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate([
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        /** @var Admin $admin */
        $admin     = auth('admin')->user();
        $google2fa = new Google2FA();

        // Try TOTP code
        if ($request->filled('code')) {
            $valid = $google2fa->verifyKey(
                decrypt($admin->two_factor_secret),
                $request->string('code')->toString(),
            );

            if ($valid) {
                session(['admin_two_factor_confirmed' => true]);

                return redirect()->intended(route('admin.dashboard'));
            }

            return back()->withErrors(['code' => __('Invalid code.')]);
        }

        // Try recovery code
        if ($request->filled('recovery_code')) {
            /** @var array<int, string> $recoveryCodes */
            $recoveryCodes = json_decode(decrypt($admin->two_factor_recovery_codes), true);
            $recoveryCode  = $request->string('recovery_code')->toString();

            if (in_array($recoveryCode, $recoveryCodes)) {
                // Remove used code
                $recoveryCodes = array_values(array_diff($recoveryCodes, [$recoveryCode]));
                $admin->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
                ])->save();

                session(['admin_two_factor_confirmed' => true]);

                return redirect()->intended(route('admin.dashboard'));
            }

            return back()->withErrors(['recovery_code' => __('Invalid recovery code.')]);
        }

        return back()->withErrors(['code' => __('Please enter a code.')]);
    }
}
