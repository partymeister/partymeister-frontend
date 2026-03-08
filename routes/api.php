<?php

use Partymeister\Frontend\Http\Controllers\Api\ProfileController;

Route::group([
    'middleware' => ['api', 'bindings'],
    'prefix'     => 'api',
    'as'         => 'api.',
], function () {
    Route::post('profile/register', [ProfileController::class, 'register']);
    Route::post('profile/login', [ProfileController::class, 'login']);
    Route::delete('profile/{api_token}/destroy', [ProfileController::class, 'destroy']);
    Route::get('profile/{api_token}/entries', [ProfileController::class, 'entries']);
    Route::get('profile/{api_token}/votes/live', [ProfileController::class, 'vote_live']);
    Route::get('profile/{api_token}/votes/entries', [ProfileController::class, 'vote_entries']);
    Route::post('profile/{api_token}/votes/{entry}/vote', [ProfileController::class, 'vote_save']);
});
