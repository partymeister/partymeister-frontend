<?php

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Partymeister\Frontend\Http\Controllers\Api\V2\ProfileAuthController;
use Partymeister\Frontend\Http\Controllers\Api\V2\ProfileEntriesController;
use Partymeister\Frontend\Http\Controllers\Api\V2\ProfileVotesController;
use Partymeister\Frontend\Http\Middleware\EnsureVisitorAuthenticated;

// V2: Sanctum-authenticated routes (SPA cookies for web, Bearer tokens for apps)
Route::group([
    'middleware' => [EnsureFrontendRequestsAreStateful::class, 'auth:sanctum', 'bindings', EnsureVisitorAuthenticated::class],
    'prefix'     => 'api/v2',
    'as'         => 'api.v2.',
], function () {
    Route::get('profile', [ProfileAuthController::class, 'show'])
         ->name('profile.show');
    Route::delete('profile', [ProfileAuthController::class, 'destroy'])
         ->name('profile.destroy');
    Route::post('profile/logout', [ProfileAuthController::class, 'logout'])
         ->name('profile.logout');

    Route::get('profile/entries', [ProfileEntriesController::class, 'index'])
         ->name('profile.entries');
    Route::get('profile/votes/live', [ProfileVotesController::class, 'live'])
         ->name('profile.votes.live');
    Route::get('profile/votes/entries', [ProfileVotesController::class, 'entries'])
         ->name('profile.votes.entries');
    Route::post('profile/votes/{entry}', [ProfileVotesController::class, 'vote'])
         ->name('profile.votes.submit');
});

// V2: Public auth routes (no auth required, throttled)
Route::group([
    'middleware' => [EnsureFrontendRequestsAreStateful::class],
    'prefix'     => 'api/v2',
    'as'         => 'api.v2.',
], function () {
    Route::post('profile/login', [ProfileAuthController::class, 'login'])
         ->middleware('throttle:5,1')
         ->name('profile.login');
    Route::post('profile/register', [ProfileAuthController::class, 'register'])
         ->middleware('throttle:5,1')
         ->name('profile.register');
});
