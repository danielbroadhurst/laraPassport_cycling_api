<?php

namespace App\Http\Controllers;

use App\User;
use App\CyclingClub;
use App\Helpers\Maps;
use App\Http\Resources\CyclingClub as ResourcesCyclingClub;
use App\Http\Resources\User as ResourcesUser;
use Illuminate\Http\Request;

class CyclingClubController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('id')) {
            $clubs = CyclingClub::where('id', $request->input('id'))->get();
        } elseif ($request->has('county')) {
            $clubs = CyclingClub::where('county', $request->input('county'))->get();
        } elseif ($request->has('search')) {
            $query = $request->input('search');
            $clubs = CyclingClub::where('club_name', 'like', "%".$query."%")->pluck('club_name', 'id');
            $response = [];
            foreach ($clubs as $key => $value) {
                $tempClub = array(
                    'id' => $key,
                    'club_name' => $value
                );
                array_push($response, $tempClub);
            }
            $clubs = $response;
        } else {
            $clubs = CyclingClub::get();
        }
        return response()->json($clubs, 200);
    }
    /**
     * Store a cycling club and profile in database.
     * Calls Geocoder API and retrieves location information.
     * Required fields are club_name, city, country.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Required Fields
        $request->validate([
            'club_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        // Get the authenticated user.
        $user = auth()->user();

        // Check user has created a profile.
        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }

        // Check if Club Name already exists
        if (sizeof(CyclingClub::where('club_name', $request->club_name)->get()) > 0) {
            $club = CyclingClub::where('club_name', $request->club_name)->get()->first();
            $errorMessage = 'The club `' . $club->club_name . '` already exists.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }

        $profilePicture = false;
        // Store Profile Picture if present on request.
        if ($request->profile_picture) {
            $profilePicture = $this->saveProfilePicture($request->profile_picture, auth()->user()->id, $request->club_name);
        }

        // Use searchMaps to get location data
        $mapsQuery = (object) array(
            'city' => $request->city,
            'country' => $request->country
        );
        $searchMap = new Maps();
        $mapResult = $searchMap->searchMaps($mapsQuery);

        // Store the cycling club in the database with the additional County Country Code, Latitude, Longitude data.
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
            'profile_picture' => $profilePicture ? $profilePicture : null,
            'is_active' => true,
        ]);

        // If successful, set user account to an admin account and return the users details else error.
        if ($cyclingClub) {
            $user->userProfile->is_admin = true;
            $user->userProfile->save();
            return response()->json(new ResourcesUser($user), 201);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }

    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $club = CyclingClub::find($id);
        return response()->json(new ResourcesCyclingClub($club));        
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

        if ($nameCheck !== null) {
            if ($nameCheck->id !== $cyclingClub->id) {
                $errorMessage = 'The club `' . $nameCheck->club_name . '` already exists.';
                $error = array(
                    'message' => $errorMessage
                );
                return response()->json($error, 400);
            }
        }

        if ($request->profile_picture) {
            $profilePicture = $this->saveProfilePicture($request->profile_picture, auth()->user()->id);
        }
        foreach ($request->request as $key => $value) {
            if ($key === 'profile_picture') {
                $value = $profilePicture;
            }
            if ($key == 'city' or $key == 'country') {
                $refreshLatLng = true;
            }
            if ($key == 'events') {
                continue;
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
            return response()->json(new ResourcesUser($user), 202);
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
        return response()->json(new ResourcesUser($user), 202);
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
        $clubs = User::find($user->id)->cyclingClubMember()->where('cycling_club_id', $cyclingClub->id)->orderBy('cycling_club_id')->get()->first();

        if ($clubs) {
            $errorMessage = 'You are already a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }

        if ($user->cyclingClubMember()->attach($cyclingClub) === null) {
            return response()->json(new ResourcesUser($user), 202);
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
        $clubs = User::find($user->id)->cyclingClubMember()->where('cycling_club_id', $cyclingClub->id)->orderBy('cycling_club_id')->get()->first();
        if (!$clubs) {
            $errorMessage = 'You aren\'t a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->cyclingClubMember()->detach($cyclingClub) === 1) {
            return response()->json(new ResourcesUser($user), 202);

        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function saveProfilePicture($picture, $id)
    {
        try {
            $dir = "club_profiles";
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
            return $e->getMessage();
        }
    }
}
