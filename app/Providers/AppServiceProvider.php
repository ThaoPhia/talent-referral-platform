<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        $this->configureRateLimiters();
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiters(): void
    {
        // Rate limiter for creating referrals. Limits to 5 per minute per user and per IP.
        RateLimiter::for('referral-creation', fn (Request $request) => $this->rateLimitingEnabled() ? [
            Limit::perMinute(5)->by('user:'.$request->user()?->id),
            Limit::perMinute(5)->by('ip:'.$request->ip()),
        ] : Limit::none());

        // Rate limiter for resending referrals. Limits to 5 per minute per user and per IP.
        RateLimiter::for('referral-resend', fn (Request $request) => $this->rateLimitingEnabled() ? [
            Limit::perMinute(5)->by('user:'.$request->user()?->id),
            Limit::perMinute(5)->by('ip:'.$request->ip()),
        ] : Limit::none());

        // Rate limiter for contact form submissions. Limits to 5 per minute per IP.
        RateLimiter::for('contact-submission', fn (Request $request) => $this->rateLimitingEnabled()
            ? Limit::perMinute(5)->by($request->ip())
            : Limit::none());

        // Rate limiter for referral invitations. Limits to 5 per minute per IP.
        RateLimiter::for('referral-invitation', fn (Request $request) => $this->rateLimitingEnabled()
            ? Limit::perMinute(5)->by($request->ip())
            : Limit::none());
    }

    /**
     * Determine if rate limiting is enabled for the application.
     *
     * @return bool
     */
    private function rateLimitingEnabled(): bool
    {
        return ! $this->app->environment('testing') || config('app.test_rate_limits', false) === true;
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
