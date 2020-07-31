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
            'cycling_club_admin' => $this->when(sizeof(CyclingClub::collection($this->cyclingClubAdmin)) > 0, CyclingClub::collection($this->cyclingClubAdmin)),
            'cycling_club_member' => $this->when(sizeof(CyclingClub::collection($this->cyclingClubMember)) > 0, CyclingClub::collection($this->cyclingClubMember)),
            'event_attendee' => $this->when(sizeof(AppUser::whereId($this->id)->with('clubEventAttendee')->first()->clubEventAttendee) > 0, function() {
                return AppUser::whereId($this->id)->with('clubEventAttendee')->first()->clubEventAttendee;
            }),
        ];
    }
}
