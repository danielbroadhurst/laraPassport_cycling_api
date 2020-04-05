<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {   
        $http = new \GuzzleHttp\Client;

        try {
            $response = $http->post(config('services.passport.login_endpoint'), [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => config('services.passport.client_id'),
                    'client_secret' => config('services.passport.client_secret'),
                    'username' => $request->email,
                    'password' => $request->password
                ]
            ]);
            
            return json_decode($response->getBody(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            dd($e);
            if ($e->getCode() === 400) {
                return response()->json('Invalid Request. Please enter a username or a password.', $e->getCode());
            } else if ($e->getCode() === 401) {
                return response()->json('Your credentials are incorrect. Please try again.', $e->getCode());
            }
            return response()->json('Something went wrong on the server.', $e->getCode());
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string:min:6'
        ]);

        $user = User::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($user) {
            return $this->login($request);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function logout()
    {
        auth()->user()->tokens->each(function($token, $key) {
            $token->delete();
        });

        return response()->json('Logged out successfully', 200);
    }

    // TODO - DELETE USER PROFILE
    public function deleteAccount(User $user)
    {
        $user = User::find($user->id)->delete();
        return response()->json('Deleted account successfully', 200);
    }
}
