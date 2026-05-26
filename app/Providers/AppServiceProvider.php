<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Services\ProfilePhotoService;
use App\Events\NotificationRecorded;
use App\Events\PostLiked;
use App\Listeners\Broadcasting\SendCommentCreatedBroadcast;
use App\Listeners\Broadcasting\SendNotificationCreatedBroadcast;
use App\Listeners\Broadcasting\SendPostLikedBroadcast;
use App\Observers\DatabaseNotificationObserver;
use App\Support\OperationalLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ProfilePhotoService::class, function ($app) {
            return new ProfilePhotoService(
                $app->make('image')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sesiones en tabla + SQLite en solo lectura (típico en Docker) rompe cada request.
        if (config('session.driver') === 'database' && config('database.default') === 'sqlite') {
            config(['session.driver' => 'file']);
        }

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-email', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('password-store', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('password-update', fn (Request $request) => Limit::perMinute(10)->by((string) $request->user()->getAuthIdentifier()));

        RateLimiter::for('feed-filter', fn (Request $request) => Limit::perMinute(120)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('tags-index', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('tags-search', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('comment-store', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()->getAuthIdentifier()));
        RateLimiter::for('like-toggle', fn (Request $request) => Limit::perMinute(60)->by((string) $request->user()->getAuthIdentifier()));
        RateLimiter::for('notifications-api', fn (Request $request) => Limit::perMinute(90)->by((string) $request->user()->getAuthIdentifier()));
        RateLimiter::for('profile-posts-json', fn (Request $request) => Limit::perMinute(120)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('username-check', fn (Request $request) => Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('settings-write', fn (Request $request) => Limit::perMinute(25)->by((string) $request->user()->getAuthIdentifier()));

        RateLimiter::for('create-post', function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('follow-toggle', function (Request $request): Limit {
            return Limit::perMinute(30)->by((string) $request->user()->getAuthIdentifier());
        });

        RateLimiter::for('eco-reanalyze', function (Request $request): Limit {
            return Limit::perMinute(8)->by((string) $request->user()->getAuthIdentifier());
        });

        Event::listen(Login::class, function (Login $event): void {
            OperationalLogger::authLogin($event->user->getAuthIdentifier(), request(), $event->remember);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user !== null) {
                OperationalLogger::authLogout($event->user->getAuthIdentifier(), request());
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $email = null;
            if (is_array($event->credentials ?? null)) {
                $e = $event->credentials['email'] ?? null;
                $email = is_string($e) ? $e : null;
            }
            OperationalLogger::authFailed($email, request());
        });

        DatabaseNotification::observe(DatabaseNotificationObserver::class);

        Event::listen(PostLiked::class, SendPostLikedBroadcast::class);
        Event::listen(CommentCreated::class, SendCommentCreatedBroadcast::class);
        Event::listen(NotificationRecorded::class, SendNotificationCreatedBroadcast::class);

        $this->forceHttpsInProduction();
    }

    private function forceHttpsInProduction(): void
    {
        if (
            filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN)
            || $this->app->environment(['production', 'qa'])
        ) {
            URL::forceScheme('https');
        }
    }
}
