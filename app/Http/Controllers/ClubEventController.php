<?php

namespace App\Http\Controllers;

use App\ClubEvent;
use App\CyclingClub;
use App\Helpers\Maps;
use App\Http\Resources\User as ResourcesUser;
use App\User;
use Illuminate\Http\Request;

class ClubEventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('id')) {
            $clubs = ClubEvent::where('id', $request->input('id'))->get();
        } elseif ($request->has('county')) {
            $clubs = ClubEvent::where('county', $request->input('county'))->get();
        } elseif ($request->has('search')) {
            $query = $request->input('search');
            $clubs = ClubEvent::where('event_name', 'like', "%".$query."%")->pluck('event_name', 'id');
            $response = [];
            foreach ($clubs as $key => $value) {
                $tempClub = array(
                    'id' => $key,
                    'event_name' => $value
                );
                array_push($response, $tempClub);
            }
            $clubs = $response;
        } else {
            $clubs = ClubEvent::get();
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

        // Required Fields
        $request->validate([
            'cycling_club_id' => 'required|int|max:20',
            'event_name' => 'required|string|max:255',
            'event_date' => 'required|date',
            'start_time' => 'date_format:H:i',
            'start_address' => 'required|string|max:255'
        ]);
        // Get User
        $user = auth()->user();
        // Get Cycling Club
        $cyclingClub = CyclingClub::where('id', $request->cycling_club_id)->get()->first();
        // Check Cycling Club Exists
        if (!$cyclingClub) {
            $errorMessage = 'Cycling Club not found.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User is Admin of Club
        if ($user->id != $cyclingClub->user_id) {
            $errorMessage = 'You must be the Club Admin to Edit a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check Event Not Duplicated - Same Name and Date
        if (sizeof(ClubEvent::where('event_name', $request->event_name)->where('event_date', $request->event_date)->get()) > 0) {
            $event = ClubEvent::where('event_name', $request->event_name)->get()->first();
            $errorMessage = 'The club `' . $event->event_name . '` already exists for '.$request->event_date.'.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Store Event Picture
        if ($request->event_picture) {
            $event_picture = $this->saveEventPicture($request->event_picture, auth()->user()->id, $request->event_name);
        }
        // Use searchMaps to get location data
        $mapsQuery = (object) array(
            'city' => $request->start_address,
            'country' => $request->city
        );
        $searchMap = new Maps();
        $mapResult = $searchMap->searchMaps($mapsQuery);
        // Store the cycling club in the database with the additional County Country Code, Latitude, Longitude data.
        $clubEvent = ClubEvent::create([
            'cycling_club_id' => $cyclingClub->id,
            'admin_id' => $user->id,
            'event_name' => $request->event_name,
            'description' => $request->description,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'start_address' => $request->start_address,
            'city' => $request->city,
            'county' => $mapResult ? $mapResult->Address->County : null,
            'country' => $mapResult ? $mapResult->Address->AdditionalData[0]->value : null,
            'country_short' => $mapResult ? $mapResult->Address->Country : null,
            'lat' => $mapResult ? $mapResult->NavigationPosition[0]->Latitude : null,
            'lng' => $mapResult ? $mapResult->NavigationPosition[0]->Longitude : null,
            'event_picture' => $event_picture ? $event_picture : null,
        ]);
        // Add Admin as an attendee to the event.
        $user->clubEventAttendee()->attach($clubEvent);
        // If successful, return the user resource.
        if ($clubEvent) {
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
        $clubEvent = ClubEvent::where('id', $id)->with('cyclingClub')->with(array('attendees' => function($query){
            $query->select('id', 'first_name', 'last_name');
        }))->get();
        if (sizeof($clubEvent) === 0) {
            $errorMessage = 'Club Event not found.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        return response()->json($clubEvent);        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $refreshLatLng = false;
        $user = auth()->user();
        $clubEvent = ClubEvent::whereId($id)->with('cyclingClub')->first();
        if (!$clubEvent) {
            $errorMessage = 'The event does not exist.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->id !== $clubEvent->cyclingClub->user_id) {
            $errorMessage = 'You must be the Club Admin to Edit a Cycling Club Event.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Store Event Picture
        if ($request->event_picture) {
            $event_picture = $this->saveEventPicture($request->event_picture, auth()->user()->id, $request->event_name);
        }
        foreach ($request->request as $key => $value) {
            if ($key === 'event_picture') {
                $value = $event_picture;
            }
            if ($key == 'start_address' or $key == 'city') {
                $refreshLatLng = true;
            }
            $clubEvent->$key = $value;
        }
        // Refresh Map Address Details
        if ($refreshLatLng) {
            $mapsQuery = (object) array(
                'city' => $request->city ? $request->city : $clubEvent->start_address,
                'country' => $request->country ? $request->country : $clubEvent->country
            );
            $searchMap = new Maps();
            $mapResult = $searchMap->searchMaps($mapsQuery);
            $clubEvent->county = $mapResult ? $mapResult->Address->County : null;
            $clubEvent->country = $mapResult ? $mapResult->Address->AdditionalData[0]->value : null;
            $clubEvent->country_short = $mapResult ? $mapResult->Address->Country : null;
            $clubEvent->lat = $mapResult ? $mapResult->NavigationPosition[0]->Latitude : null;
            $clubEvent->lng = $mapResult ? $mapResult->NavigationPosition[0]->Longitude : null;
        }
        // Add Admin as an attendee to the event.
        if (sizeof($user->clubEventAttendee) < 1) {
            $user->clubEventAttendee()->attach($clubEvent);
        };
        // Save Event
        if ($clubEvent->save()) {
            return response()->json(new ResourcesUser($user), 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $clubEvent = ClubEvent::whereId($id)->with('cyclingClub')->first();
        if (!$clubEvent) {
            $errorMessage = 'The event does not exist.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->id !== $clubEvent->cyclingClub->user_id) {
            $errorMessage = 'You must be the Club Admin to Edit a Cycling Club Event.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        ClubEvent::find($clubEvent->id)->delete();
        return response()->json(new ResourcesUser($user), 202);
    }

    public function attendClubEvent($id)
    {
        $user = auth()->user();
        // Check Event Exists
        $clubEvent = ClubEvent::whereId($id)->with('cyclingClub')->first();
        if (!$clubEvent) {
            $errorMessage = 'Club Event not found.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User has a Profile
        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User is not the admin for the event
        $clubAdmin = User::find($user->id)->cyclingClubAdmin()->where('user_id', $clubEvent->cyclingClub->id)->get()->first();
        if($clubAdmin) {
            $errorMessage = 'You are the Admin for this Club Event.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User is a member or admin of cycling club
        $clubMember = User::find($user->id)->cyclingClubMember()->where('cycling_club_id', $clubEvent->cyclingClub->id)->get()->first();
        if (!$clubMember) {
            $errorMessage = 'You are not a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->clubEventAttendee()->attach($clubEvent) === null) {
            return response()->json(new ResourcesUser($user), 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function leaveClubEvent($id)
    {
        $user = auth()->user();
        // Check event exists
        $clubEvent = ClubEvent::whereId($id)->with('cyclingClub')->first();
        if (!$clubEvent) {
            $errorMessage = 'Club Event not found.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User Has a Profile
        if (!$user->userProfile) {
            $errorMessage = 'You must create a User Profile before creating a Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User is not the admin for the event
        $clubAdmin = User::find($user->id)->cyclingClubAdmin()->where('user_id', $clubEvent->cyclingClub->id)->get()->first();
        if($clubAdmin) {
            $errorMessage = 'You are the Admin for this Club Event.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        // Check User is a member or admin of cycling club
        $clubMember = User::find($user->id)->cyclingClubMember()->where('cycling_club_id', $clubEvent->cyclingClub->id)->get()->first();
        if (!$clubMember) {
            $errorMessage = 'You are not a member of this Cycling Club.';
            $error = array(
                'message' => $errorMessage
            );
            return response()->json($error, 400);
        }
        if ($user->clubEventAttendee()->detach($clubEvent) === 1) {
            return response()->json(new ResourcesUser($user), 202);
        } else {
            return response()->json('Something went wrong on the server.', 400);
        }
    }

    public function saveEventPicture($picture, $id, $name)
    {
        try {
            $dir = "club_events";
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
            $name = urlencode(str_replace(' ', '', $name));
            
            $savePath = $dir. '/'. $id . '/' . $name . '.' . $ext;

            file_put_contents(public_path().'/'.$savePath, $data);
            
            return $savePath;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
