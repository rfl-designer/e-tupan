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
                    <div class="flex flex-col gap-6">
                        <x-auth-header
                            :title="__('Admin Panel')"
                            :description="__('Enter your credentials to access the admin area')"
                        />

                        <!-- Session Status -->
                        <x-auth-session-status class="text-center" :status="session('status')" />

                        <form method="POST" action="{{ route('admin.login.store') }}" class="flex flex-col gap-6">
                            @csrf

                            <!-- Email Address -->
                            <flux:input
                                name="email"
                                :label="__('Email address')"
                                :value="old('email')"
                                type="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="admin@example.com"
                            />

                            <!-- Password -->
                            <flux:input
                                name="password"
                                :label="__('Password')"
                                type="password"
                                required
                                autocomplete="current-password"
                                :placeholder="__('Password')"
                                viewable
                            />

                            <!-- Remember Me -->
                            <div class="flex items-center justify-between">
                                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />
                                <flux:link :href="route('admin.password.request')" class="text-sm">
                                    {{ __('Forgot password?') }}
                                </flux:link>
                            </div>

                            <div class="flex items-center justify-end">
                                <flux:button variant="primary" type="submit" class="w-full" data-test="admin-login-button">
                                    {{ __('Log in') }}
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
