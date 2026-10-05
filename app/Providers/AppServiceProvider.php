<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(\App\Support\Years::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Stylesheets/scripts are referenced as /build/... so they load whatever scheme or host the visitor used.
        \Illuminate\Support\Facades\Vite::createAssetPathsUsing(fn (string $path, ?bool $secure = null) => '/'.ltrim($path, '/'));

        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($user, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false));

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(__('Reset your password').' – '.config('school.name'))
                ->greeting(__('Hello :name', ['name' => $user->name]))
                ->line(__('We received a request to reset the password of your account.'))
                ->action(__('Reset password'), $url)
                ->line(__('This link expires in :n minutes.', ['n' => config('auth.passwords.users.expire')]))
                ->line(__('If you did not ask for this, you can ignore this email.'));
        });

        if (str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        //
    }
}
