<?php

use Partymeister\Frontend\Http\Controllers\Api\ProfileController;
use Partymeister\Frontend\Http\Controllers\Api\V2\ProfileEntriesController;
use Partymeister\Frontend\Http\Controllers\Api\V2\ProfileVotesController;

Route::group([
    'middleware' => ['api', 'bindings'],
    'prefix'     => 'api',
    'as'         => 'api.',
], function () {
    Route::post('profile/register', [ProfileController::class, 'register'])
         ->middleware('throttle:5,1');
    Route::post('profile/login', [ProfileController::class, 'login'])
         ->middleware('throttle:5,1');
    Route::delete('profile/{api_token}/destroy', [ProfileController::class, 'destroy']);
    Route::get('profile/{api_token}/entries', [ProfileController::class, 'entries']);
    Route::get('profile/{api_token}/votes/live', [ProfileController::class, 'vote_live']);
    Route::get('profile/{api_token}/votes/entries', [ProfileController::class, 'vote_entries']);
    Route::post('profile/{api_token}/votes/{entry}/vote', [ProfileController::class, 'vote_save']);
});

// V2: Sanctum SPA cookie-authenticated routes (web frontend)
Route::group([
    'middleware' => ['web', 'auth:sanctum'],
    'prefix'     => 'api/v2',
    'as'         => 'api.v2.',
], function () {
    Route::get('profile/entries', [ProfileEntriesController::class, 'index'])
         ->name('profile.entries');
    Route::get('profile/votes/live', [ProfileVotesController::class, 'live'])
         ->name('profile.votes.live');
    Route::get('profile/votes/entries', [ProfileVotesController::class, 'entries'])
         ->name('profile.votes.entries');
    Route::post('profile/votes/{entry}', [ProfileVotesController::class, 'vote'])
         ->name('profile.votes.submit');
});
