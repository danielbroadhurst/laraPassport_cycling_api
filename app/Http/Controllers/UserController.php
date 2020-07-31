<?php

namespace App\Http\Controllers;

use App\Http\Resources\User as ResourcesUser;
use App\User;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->userProfile && $user) {
            return response()->json(new ResourcesUser($user));
        } else {
            return response()->json(User::where('id', $user->id)->with('userProfile')->get());
        }
    }
}
