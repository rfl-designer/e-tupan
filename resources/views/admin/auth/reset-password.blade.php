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
                        :title="__('Reset Admin Password')"
                        :description="__('Please enter your new password below')"
                    />

                    <!-- Session Status -->
                    <x-auth-session-status class="text-center" :status="session('status')" />

                    <form method="POST" action="{{ route('admin.password.update') }}" class="flex flex-col gap-6">
                        @csrf

                        <!-- Token -->
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Email Address -->
                        <flux:input
                            name="email"
                            :value="old('email', $email)"
                            :label="__('Email address')"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="admin@example.com"
                        />

                        <!-- Password -->
                        <flux:input
                            name="password"
                            :label="__('New Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('New Password')"
                            viewable
                        />

                        <!-- Confirm Password -->
                        <flux:input
                            name="password_confirmation"
                            :label="__('Confirm Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Confirm Password')"
                            viewable
                        />

                        <flux:button variant="primary" type="submit" class="w-full" data-test="admin-reset-password-button">
                            {{ __('Reset Password') }}
                        </flux:button>
                    </form>

                    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
                        <span>{{ __('Or, return to') }}</span>
                        <flux:link :href="route('admin.login')">{{ __('log in') }}</flux:link>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
