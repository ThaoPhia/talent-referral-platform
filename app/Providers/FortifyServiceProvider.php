<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorConfirmedResponse as TwoFactorConfirmedResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->instance(RegisterResponseContract::class, new class() implements RegisterResponseContract {
            public function toResponse($request)
            {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Your referrer application is pending review. We will email you when it is approved.');
            }
        });

        $this->app->instance(
            TwoFactorConfirmedResponseContract::class,
            new class() implements TwoFactorConfirmedResponseContract {
                public function toResponse($request)
                {
                    Inertia::flash('success_toast', 'Two-factor authentication is now active');

                    return $request->wantsJson()
                        ? new JsonResponse('', 200)
                        : back()->with('status', Fortify::TWO_FACTOR_AUTHENTICATION_CONFIRMED);
                }
            }
        );
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->input(Fortify::username()))->first();

            // Only allow authentication if the user is active
            return $user && $user->status === 'active' && Hash::check($request->input('password'), $user->password)
                ? $user
                : null;
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(LoginController::class);

        Fortify::registerView(RegisterController::class);

        Fortify::requestPasswordResetLinkView(fn(Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn(Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::verifyEmailView(fn(Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::confirmPasswordView(fn() => Inertia::render('auth/ConfirmPassword'));

        Fortify::twoFactorChallengeView(fn() => Inertia::render('auth/TwoFactorChallenge'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
