<?php

namespace App\Http\Controllers;

use App\User;
use App\UserProfile;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = auth()->user();
        
        $request->validate([
            'date_of_birth' => 'required|date',
            'country' => 'required|string|max:255',
        ]);

        if (!User::where('id', $user->id)->userProfile) {
            dd($profile = UserProfile::create([
                'user_id' => $user->id,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'town' => $request->town,
                'region' => $request->region,
                'country' => $request->country,
                'current_bike' => $request->current_bike,
                'preferred_style' => $request->preferred_style,
                'profile_picture' => $request->profile_picture,
                'bio' => $request->bio
            ]));

            if ($profile) {
                return response()->json(User::where('id',$user->id)->with('userProfile')->get());
            } else {
                return response()->json('Something went wrong on the server.', 400);
            }
        } else {
            $userProfile = User::find($user->id)->userProfile;
            return $this->update($request, $userProfile);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\UserProfile  $userProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserProfile $userProfile)
    {
        foreach ($request->request as $key => $value) {
            $userProfile->$key = $value;
        }
        
        if ($userProfile->save()) {
            return response()->json(User::where('id', $userProfile->user_id)->with('userProfile')->get());
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
        dd($userProfile);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\UserProfile  $userProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserProfile $userProfile)
    {
        //
    }
}
