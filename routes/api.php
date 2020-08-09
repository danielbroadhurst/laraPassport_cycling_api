<?php

Use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\User as ResourcesUser;

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

/* Provides Login endpoint for Passport */
Route::post('/login', 'AuthController@Login');
Route::post('/register', 'AuthController@Register');

/* User Profile Group */
Route::middleware('auth:api')->group(function () {
    Route::get('/user/{user}', function (User $user = null) {
        return response()->json(new ResourcesUser($user));
    });
    Route::resource('user-profile', 'UserProfileController')->only([
        'store', 'update'
    ]);
    Route::apiResources([
        'events' => 'ClubEventController',
        'cycling-club' => 'CyclingClubController'
    ]);
    Route::get('/countries', 'CountriesController@Index');
    Route::post('/logout', 'AuthController@Logout');
    Route::delete('/delete-account/{user}', 'AuthController@DeleteAccount');
    /* View User with Clubs and Club Admin */
    Route::get('/user', 'UserController@index');
    /* User Join & Leave Club */
    Route::post('/join-cycling-club/{cyclingClub}', 'CyclingClubController@joinCyclingClub');
    Route::post('/leave-cycling-club/{cyclingClub}', 'CyclingClubController@leaveCyclingClub');
    /* User Join & Leave Club Event */
    Route::post('/attend-club-event/{id}', 'ClubEventController@attendClubEvent');
    Route::post('/leave-club-event/{id}', 'ClubEventController@leaveClubEvent');
});

Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact daniel@cycling-club.com'], 404);
});
