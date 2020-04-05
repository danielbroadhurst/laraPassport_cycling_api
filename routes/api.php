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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    $user = auth()->user();
    return User::find($user->id)->with('userProfile')->get();
});

/* Provides Login endpoint for Passport */
Route::post('/login', 'AuthController@Login');
Route::post('/register', 'AuthController@Register');
Route::middleware('auth:api')->post('/logout', 'AuthController@Logout');
Route::middleware('auth:api')->delete('/delete-account/{user}', 'AuthController@DeleteAccount');
/* User Profile Group */
Route::middleware('auth:api')->group(function () {
    Route::resource('userProfile', 'UserProfileController')->only([
        'store', 'update'
    ]);
});