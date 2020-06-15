<?php

Use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/images', 'ImageController@upload');

Route::middleware('auth:api')->get('/user/{user}', function (User $user = null) {
    return response()->json(User::where('id',$user->id)->with('userProfile')->get());
});

/* Provides Login endpoint for Passport */
Route::post('/login', 'AuthController@Login');
Route::post('/register', 'AuthController@Register');
Route::middleware('auth:api')->post('/logout', 'AuthController@Logout');
Route::middleware('auth:api')->delete('/delete-account/{user}', 'AuthController@DeleteAccount');

/* View User with Clubs and Club Admin */
Route::middleware('auth:api')->get('/user', 'UserController@index');
/* User Join & Leave Club */
Route::middleware('auth:api')->post('/join-cycling-club/{cyclingClub}', 'CyclingClubController@joinCyclingClub');
Route::middleware('auth:api')->post('/leave-cycling-club/{cyclingClub}', 'CyclingClubController@leaveCyclingClub');

/* User Profile Group */
Route::middleware('auth:api')->group(function () {
    Route::resource('user-profile', 'UserProfileController')->only([
        'store', 'update'
    ]);
    Route::resource('cycling-club', 'CyclingClubController')->only([
        'store', 'update', 'index', 'destroy'
    ]);
    Route::get('/countries', 'CountriesController@Index');
});

Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
});