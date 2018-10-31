<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('emptyRequest')->group(function(){
	Route::get('register', 'AuthController@register')->name('api.register');
	Route::get('login', 'AuthController@login')->name('api.login');
	Route::get('logout', 'AuthController@logout')->name('api.logout');
});

Route::middleware(['verifyUserToken'])->group(function(){
	Route::post('profile', 'UserController@updateProfile')->name('profile.update');
	Route::prefix('event')->group(function(){
		Route::post('/', 'EventController@createEvent')->name('event.create');
	});
});