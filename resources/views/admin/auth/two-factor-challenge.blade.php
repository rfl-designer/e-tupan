<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <div class="flex flex-col items-center gap-2 font-medium">
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </div>
                <div class="flex flex-col gap-6">
                    <x-auth-header
                        :title="__('Two-Factor Authentication')"
                        :description="__('Enter the code from your authenticator app to continue.')"
                    />

                    <!-- TOTP Code Form -->
                    <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="flex flex-col gap-6" id="totp-form">
                        @csrf

                        <flux:input
                            name="code"
                            :label="__('Authentication Code')"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            autofocus
                            :placeholder="__('Enter 6-digit code')"
                        />

                        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-2fa-verify-button">
                            {{ __('Verify') }}
                        </flux:button>
                    </form>

                    <flux:separator />

                    <!-- Recovery Code Form -->
                    <div x-data="{ showRecovery: false }">
                        <button
                            type="button"
                            @click="showRecovery = !showRecovery"
                            class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 w-full text-center"
                        >
                            <span x-show="!showRecovery">{{ __('Use a recovery code instead') }}</span>
                            <span x-show="showRecovery" x-cloak>{{ __('Use authentication code') }}</span>
                        </button>

                        <form
                            method="POST"
                            action="{{ route('admin.two-factor.verify') }}"
                            class="flex flex-col gap-6 mt-4"
                            x-show="showRecovery"
                            x-cloak
                        >
                            @csrf

                            <flux:input
                                name="recovery_code"
                                :label="__('Recovery Code')"
                                type="text"
                                autocomplete="off"
                                :placeholder="__('Enter recovery code')"
                            />

                            <flux:button variant="primary" type="submit" class="w-full" data-test="admin-2fa-recovery-button">
                                {{ __('Verify Recovery Code') }}
                            </flux:button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
