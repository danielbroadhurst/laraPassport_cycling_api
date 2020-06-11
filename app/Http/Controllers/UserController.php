<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->userProfile && $user) {
            return response()->json(User::where('id', $user->id)->with('userProfile')->with('cyclingClubAdmin')->get());
        } else {
            return response()->json(User::where('id', $user->id)->with('userProfile')->get());
        }
    }
}
