<?php

namespace App\Http\Controllers;

use App\Http\Resources\User as ResourcesUser;
use App\User;

class UserController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        return response()->json(new ResourcesUser($user));
    }
}
