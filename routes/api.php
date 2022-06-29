<?php

Route::group([
    'middleware' => ['api', 'bindings'],
    'namespace'  => 'Partymeister\Frontend\Http\Controllers\Api',
    'prefix'     => 'api',
    'as'         => 'api.',
], function () {
    Route::post('profile/register', 'ProfileController@register');
    Route::post('profile/login', 'ProfileController@login');
    Route::get('profile/{api_token}/entries', 'ProfileController@entries');
    Route::get('profile/{api_token}/votes/live', 'ProfileController@vote_live');
    Route::get('profile/{api_token}/votes/entries', 'ProfileController@vote_entries');
    Route::post('profile/{api_token}/votes/{entry}/vote', 'ProfileController@vote_save');
});
