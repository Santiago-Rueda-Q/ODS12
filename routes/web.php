<?php

use App\Http\Controllers\FollowController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InternalMetricsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsernameAvailabilityController;
use App\Http\Controllers\WallController;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('welcome');
})->name('home');

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::view('/explorar', 'explore')->name('explore');

Route::view('/como-funciona', 'how-it-works')->name('how-it-works');

Route::get('/health', HealthController::class)
    ->middleware('throttle:120,1')
    ->name('health');

Route::get('/internal/metrics', InternalMetricsController::class)
    ->middleware('throttle:30,1')
    ->name('internal.metrics');

Route::get('/tags', [TagController::class, 'index'])
    ->middleware('throttle:tags-index')
    ->name('tags.index');

Route::get('/tags/search', [TagController::class, 'search'])
    ->middleware('throttle:tags-search')
    ->name('tags.search');

Route::get('/posts/filter', [WallController::class, 'filter'])
    ->middleware('throttle:feed-filter')
    ->name('posts.filter');

Route::get('/posts', function (Request $request) {
    if (! auth()->check()) {
        return redirect()->route('welcome');
    }
    $search = $request->query('search');
    if (is_string($search) && trim($search) !== '') {
        return redirect()->route('dashboard', ['search' => $search]);
    }

    return redirect()->route('dashboard');
})->name('posts.index');

Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

Route::post('/posts/{post}/comments', [PostCommentController::class, 'store'])
    ->middleware(['auth', 'throttle:comment-store'])
    ->name('posts.comments.store');

Route::post('/posts/{post}/likes/toggle', [PostLikeController::class, 'toggle'])
    ->middleware(['auth', 'throttle:like-toggle'])
    ->name('posts.likes.toggle');

/*
| GET /posts/{post}/reanalyze no ejecuta el reanálisis (eso es POST).
| Sin esta ruta, abrir el URL en el navegador devolvía 405 Method Not Allowed.
*/
Route::get('/posts/{post}/reanalyze', function (Post $post) {
    return redirect()->route('posts.show', $post);
});

Route::post('/posts/{post}/reanalyze', [PostController::class, 'reanalyze'])
    ->middleware(['auth', 'throttle:eco-reanalyze'])
    ->name('posts.reanalyze');

Route::post('/posts', [PostController::class, 'store'])
    ->middleware(['auth', 'throttle:create-post'])
    ->name('posts.store');

Route::patch('/posts/{post}', [PostController::class, 'update'])
    ->middleware(['auth', 'throttle:create-post'])
    ->name('posts.update');

Route::put('/posts/{post}', [PostController::class, 'update'])
    ->middleware(['auth', 'throttle:create-post']);

Route::get('/username/availability', UsernameAvailabilityController::class)
    ->middleware('throttle:username-check')
    ->name('username.availability');

Route::get('/user/{username}', function (string $username) {
    return redirect()->route('profile.show', ['username' => $username], 301);
})->where('username', '[a-z0-9_-]+');

Route::middleware(['auth', 'throttle:profile-posts-json'])
    ->get('/profile/likes', [ProfileController::class, 'likedPosts'])
    ->name('profile.likes');

Route::get('/profile/{username}', [UserController::class, 'show'])
    ->where('username', '[a-z0-9_-]+')
    ->name('profile.show');

Route::get('/users/{username}/posts', [UserController::class, 'posts'])
    ->where('username', '[a-z0-9_-]+')
    ->middleware('throttle:profile-posts-json')
    ->name('users.posts.index');

Route::get('/dashboard', [WallController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'throttle:notifications-api'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['patch', 'post'], '/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
});

Route::middleware('auth')->group(function () {
    Route::post('/users/{username}/follow', [FollowController::class, 'store'])
        ->where('username', '[a-z0-9_-]+')
        ->middleware('throttle:follow-toggle')
        ->name('users.follow.store');
    Route::delete('/users/{username}/follow', [FollowController::class, 'destroy'])
        ->where('username', '[a-z0-9_-]+')
        ->middleware('throttle:follow-toggle')
        ->name('users.follow.destroy');

    Route::get('/profile', function () {
        return redirect()->route('settings.profile');
    })->name('profile.redirect');

    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:settings-write')
        ->name('settings.profile.update');
    Route::delete('/settings/profile', [ProfileController::class, 'destroy'])
        ->middleware('throttle:settings-write')
        ->name('settings.profile.destroy');
    Route::get('/settings/account', [ProfileController::class, 'account'])->name('settings.account');
});

require __DIR__.'/auth.php';
