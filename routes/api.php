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
		Route::get('/', 'EventController@showEvent')->name('event.show');
		Route::post('create', 'EventController@createEvent')->name('event.create');
		Route::post('update', 'EventController@updateEvent')->name('event.update');
		Route::post('new', 'EventFileController@store')->name('eventfile.store');
		Route::get('{id}', 'EventController@showFile')->name('event.file');
		Route::post('/note/{id}', 'EventFileController@updateNote')->name('file.note');
		Route::post('/file/{id}/delete', 'EventFileController@delete')->name('file.delete');
		Route::post('/delete', 'EventController@delete')->name('event.delete');
		Route::get('/users/{id}', 'EventController@user')->name('event.users');
		Route::post('/invite/{eventId}', 'EventController@invite')->name('event.invite');
	});

	Route::prefix('user')->group(function(){
		Route::get('search/{username}', 'FriendController@search')->name('friend.search');
		Route::post('/add/{userid}', 'FriendController@add')->name('friend.add');
	});

	Route::get('friend', 'FriendController@friends')->name('friend.mine');

});