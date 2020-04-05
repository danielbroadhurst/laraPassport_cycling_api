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
            'dateOfBirth' => 'required|date',
            'country' => 'required|string|max:255',
        ]);

        if (!User::find($user->id)->userProfile) {
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'gender' => $request->gender,
                'date_of_birth' => $request->dateOfBirth,
                'town' => $request->town,
                'region' => $request->region,
                'country' => $request->country,
                'current_bike' => $request->currentBike,
                'preferred_style' => $request->preferredStyle,
                'profile_picture' => $request->proflePicture,
                'bio' => $request->bio
            ]);

            if ($profile) {
                return response()->json(User::find($user->id)->with('userProfile')->get());
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
            switch ($key) {
                case 'dateOfBirth':
                    $key = 'date_of_birth';
                    break;
                case 'currentBike':
                    $key = 'current_bike';
                    break;
                case 'preferredStyle':
                    $key = 'preferred_style';
                    break;
                case 'proflePicture':
                    $key = 'profile_picture';
                    break;
                default:
                    
                    break;
            }
            $userProfile->$key = $value;
        }
        
        if ($userProfile->save()) {
            return response()->json(User::find($userProfile->user_id)->with('userProfile')->get());
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
