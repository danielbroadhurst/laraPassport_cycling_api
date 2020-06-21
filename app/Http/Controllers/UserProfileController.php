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

        if ($user->userProfile === null) {
            if ($request->profile_picture) {
                $profilePicture = $this->saveProfilePicture($request->profile_picture, $user->id, $user->email);
            }

            $profile = UserProfile::create([
                'user_id' => $user->id,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'town' => $request->town,
                'region' => $request->region,
                'country' => $request->country,
                'current_bike' => $request->current_bike,
                'preferred_style' => $request->preferred_style,
                'profile_picture' => $request->profile_picture ? $profilePicture : null,
                'bio' => $request->bio
            ]);

            if ($profile) {
                return response()->json(User::where('id', $user->id)->with('userProfile')->with('cyclingClubAdmin')->with('cyclingClubMember')->get(), 201);
            } else {
                return response()->json('Something went wrong on the server.', 400);
            }
        } else {
            $userProfile = User::find($user->id)->userProfile;
            if ($request->profile_picture) {
                $profilePicture = $this->saveProfilePicture($request->profile_picture, $user->id, $user->email);
            }
    
            foreach ($request->request as $key => $value) {
                if ($key === 'profile_picture') {
                    $value = $profilePicture;
                }
                $userProfile->$key = $value;
            }
            $userProfileSave = $userProfile->save();
            if ($userProfileSave) {
                return response()->json(User::where('id', $user->id)->with('userProfile')->with('cyclingClubAdmin')->with('cyclingClubMember')->get(), 202);
            } else {
                return response()->json('Something went wrong on the server.', 400);
            }
        }
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

    public function saveProfilePicture($picture, $id)
    {
        try {
            $dir = "profile_pictures";
            if( is_dir($dir) === false )
            {
                mkdir($dir);
            }
            if( is_dir($dir.'/'.$id) === false )
            {
                mkdir($dir.'/'.$id);
            }
            list($mime, $data)   = explode(';', $picture);
            list(, $data)       = explode(',', $data);
            $data = base64_decode($data);
            $mime = explode(':',$mime)[1];
            $ext = explode('/',$mime)[1];
            $name = mt_rand().time();
            
            $savePath = $dir. '/'. $id . '/' . $name . '.' . $ext;

            file_put_contents(public_path().'/'.$savePath, $data);
            
            return $savePath;
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
