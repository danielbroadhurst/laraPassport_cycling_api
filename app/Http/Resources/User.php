<?php

namespace App\Http\Resources;

use App\Http\Resources\CyclingClub;
use App\User as AppUser;
use ClubEvent;
use Illuminate\Http\Resources\Json\JsonResource;

class User extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array
   */
  public function toArray($request)
  {
    return [
      'id' => $this->id,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'email' => $this->email,
      'user_profile' => $this->userProfile,
      'cycling_club_admin' => $this->when(CyclingClub::collection($this->cyclingClubAdmin)->isNotEmpty(), CyclingClub::collection($this->cyclingClubAdmin)),
      'cycling_club_member' => $this->when(CyclingClub::collection($this->cyclingClubMember)->isNotEmpty(), CyclingClub::collection($this->cyclingClubMember)),
      'event_attendee' => $this->when(AppUser::whereId($this->id)->with('clubEventAttendee')->first()->clubEventAttendee->isNotEmpty(), function () {
        $events = AppUser::whereId($this->id)->with('clubEventAttendee')->first()->clubEventAttendee->pluck('cycling_club_id', 'id');
        $returnArray = array();
        foreach ($events as $key => $value) {
          $tempArray = array(
            'cycling_club_id' => $value,
            'event_id' => $key
          );
          array_push($returnArray, $tempArray);
        }
        return $returnArray;
      }),
    ];
  }
}
