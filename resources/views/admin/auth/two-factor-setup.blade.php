<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-2">
                <div class="flex flex-col items-center gap-2 font-medium">
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </div>
                <div class="flex flex-col gap-6">
                    <x-auth-header
                        :title="__('Two-Factor Authentication Setup')"
                        :description="__('Scan the QR code below with your authenticator app to enable two-factor authentication.')"
                    />

                    <div class="flex flex-col items-center gap-6">
                        <!-- QR Code -->
                        <div class="rounded-lg bg-white p-4">
                            {!! $qrCodeSvg !!}
                        </div>

                        <!-- Recovery Codes -->
                        <flux:callout variant="warning" class="w-full">
                            <flux:callout.heading>{{ __('Recovery Codes') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ __('Store these recovery codes in a secure location. They can be used to access your account if you lose your authenticator device.') }}
                            </flux:callout.text>
                        </flux:callout>

                        <div class="grid grid-cols-2 gap-2 rounded-lg bg-zinc-100 p-4 font-mono text-sm dark:bg-zinc-800 w-full">
                            @foreach ($recoveryCodes as $code)
                                <div class="text-center text-zinc-700 dark:text-zinc-300">{{ $code }}</div>
                            @endforeach
                        </div>

                        <!-- Confirmation Form -->
                        <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="flex w-full flex-col gap-4">
                            @csrf

                            <flux:input
                                name="code"
                                :label="__('Verification Code')"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                required
                                autofocus
                                :placeholder="__('Enter 6-digit code')"
                            />

                            <flux:button variant="primary" type="submit" class="w-full" data-test="admin-2fa-confirm-button">
                                {{ __('Confirm and Enable') }}
                            </flux:button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
