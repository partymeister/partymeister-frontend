<?php

use Partymeister\Frontend\Http\Controllers\Api\ProfileController;

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
