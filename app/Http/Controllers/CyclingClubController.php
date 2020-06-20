<?php

namespace App\Http\Controllers;

use App\User;
use App\CyclingClub;
use App\Helpers\Maps;
use Illuminate\Http\Request;

class CyclingClubController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('id')) {
            $clubs = CyclingClub::where('id', $request->input('id'))->get();
        }
        elseif ($request->has('county')) {
            $clubs = CyclingClub::where('county', $request->input('county'))->get();
        } else {
            $clubs = CyclingClub::get();
        }
        
        return response()->json($clubs, 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $user = auth()->user();

        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        
        $request->validate([
            'club_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        if (sizeof(CyclingClub::where('club_name', $request->club_name)->get()) > 0) {
            $club = CyclingClub::where('club_name', $request->club_name)->get()->first();
            $errorMessage = 'The club `' . $club->club_name . '` already exists.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }

        if ($request->profile_picture) {
            $profilePicture = $this->saveProfilePicture($request->profile_picture, $user->id, $request->club_name);
        }

        $mapsQuery = (object) array(
            'city' => $request->city,
            'country' => $request->country
        );
        $searchMap = new Maps();
        $mapResult = $searchMap->searchMaps($mapsQuery);

        $cyclingClub = CyclingClub::create([
            'user_id' => $user->id,
            'club_name' => $request->club_name,
            'bio' => $request->bio,
            'city' => $request->city,
            'county' => $mapResult ? $mapResult->Address->County : null,
            'country' => $request->country,
            'country_short' => $mapResult ? $mapResult->Address->Country : null,
            'lat' => $mapResult ? $mapResult->NavigationPosition[0]->Latitude : null,
            'lng' => $mapResult ? $mapResult->NavigationPosition[0]->Longitude : null,
            'preferred_style' => $request->preferred_style,
            'profile_picture' => $user->profilePicture ? $profilePicture : null,
            'is_active' => true,
        ]);

        if ($cyclingClub) {
            $user->userProfile->is_admin = true;
            $user->userProfile->save();
            return response()->json(User::where('id', $user->id)->with('userProfile')->with('cyclingClubAdmin')->get(), 201);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\CyclingClub  $cyclingClub
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CyclingClub $cyclingClub)
    {
        $profilePicture = null;
        $refreshLatLng = false;

        $user = auth()->user();

        if ($user->id != $cyclingClub->user_id) {
            $errorMessage = 'You must be the Club Admin to Edit a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        $nameCheck = CyclingClub::where('club_name', $request->club_name)->get()->first();
        if ($nameCheck->id !== $cyclingClub->id) {
            $errorMessage = 'The club `' . $nameCheck->club_name . '` already exists.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($request->profile_picture) {
            $profilePicture = $this->saveProfilePicture($request->profile_picture, auth()->user()->id, $cyclingClub->club_name);
        }
        foreach ($request->request as $key => $value) {
            if ($key === 'profile_picture') {
                $value = $profilePicture;
            }
            if ($key == 'city' or $key == 'country') {
                $refreshLatLng = true;
            }
            $cyclingClub->$key = $value;
        }

        if ($refreshLatLng) {
            $mapsQuery = (object) array(
                'city' => $request->city ? $request->city : $cyclingClub->city,
                'country' => $request->country ? $request->country : $cyclingClub->country
            );
            $searchMap = new Maps();
            $mapResult = $searchMap->searchMaps($mapsQuery);
            $cyclingClub->lat = $mapResult ? $mapResult->NavigationPosition[0]->Latitude : null;
            $cyclingClub->lng = $mapResult ? $mapResult->NavigationPosition[0]->Longitude : null;
        }

        if ($cyclingClub->save()) {
            return response()->json(User::where('id', $user->id)->with('userProfile')->with('cyclingClubAdmin')->get(), 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\CyclingClub  $cyclingClub
     * @return \Illuminate\Http\Response
     */
    public function destroy(CyclingClub $cyclingClub)
    {
            $user = auth()->user();
        if ($user->id !== $cyclingClub->user_id) {
            $errorMessage = 'You must be the Club Admin to Edit a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if (!CyclingClub::where('id', $cyclingClub->id)->get()) {
            $errorMessage = 'The club does not exist.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        $cyclingClub = CyclingClub::find($cyclingClub->id)->delete();
        return response()->json(User::where('id', $user->id)->with('cyclingClubAdmin')->get(), 202);
    }

    public function joinCyclingClub(CyclingClub $cyclingClub)
    {
        $user = auth()->user();
        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        $clubs = User::find($user->id)->cyclingClubs()->orderBy('cycling_club_id')->get()->first();

        if ($clubs) {
            $errorMessage = 'You are already a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }

        if ($user->cyclingClubs()->attach($cyclingClub) === null) {
            return response()->json($cyclingClub, 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function leaveCyclingClub(CyclingClub $cyclingClub)
    {
        $user = auth()->user();
        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        $clubs = User::find($user->id)->cyclingClubs()->orderBy('cycling_club_id')->get()->first();
        if (!$clubs) {
            $errorMessage = 'You aren\'t a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->cyclingClubs()->detach($cyclingClub) === 1) {
            return response()->json($cyclingClub, 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function saveProfilePicture($picture, $id)
    {
        try {
            $dir = "uploads";
            if( is_dir($dir) === false )
            {
                mkdir($dir);
            }
            if( is_dir($dir.'/'.$id) === false )
            {
                mkdir($dir.'/'.$id);
            }
/*             list($mime, $data)   = explode(';', $picture);
            list(, $data)       = explode(',', $data);
            $data = base64_decode($data);
            $mime = explode(':',$mime)[1];
            $ext = explode('/',$mime)[1];
            $name = mt_rand().time(); */
            
            $savePath = 'uploads/'. $id . '/' . $picture->name;

            file_put_contents(public_path().'/'.$savePath, $picture);
            
            return '/'.$savePath;
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
