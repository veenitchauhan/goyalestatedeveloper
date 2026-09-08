<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AuditRecorder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        foreach (collect(config('permissions'))->pluck('permissions')->flatten()->unique() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }
        foreach ([TwoFactorAuthenticationEnabled::class => 'auth.two_factor_enabled', TwoFactorAuthenticationConfirmed::class => 'auth.two_factor_confirmed', TwoFactorAuthenticationDisabled::class => 'auth.two_factor_disabled', RecoveryCodesGenerated::class => 'auth.recovery_codes_generated', PasswordUpdatedViaController::class => 'auth.password_updated'] as $eventClass => $action) {
            Event::listen($eventClass, fn ($event) => app(AuditRecorder::class)->record($action, $event->user, actorId: $event->user->id));
        }
        Event::listen(Login::class, fn (Login $event) => app(AuditRecorder::class)->record('auth.login', $event->user, actorId: $event->user->id));
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                app(AuditRecorder::class)->record('auth.logout', $event->user, actorId: $event->user->id);
            }
        });
    }
}
