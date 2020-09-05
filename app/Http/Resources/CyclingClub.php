<?php

namespace App\Http\Resources;
use App\User as AppUser;

use Illuminate\Http\Resources\Json\JsonResource;

class CyclingClub extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $admin = AppUser::where('id', $this->user_id)->first();
        return [
            'id' => $this->id,
            'admin' => $admin->first_name . " " . $admin->last_name,
            'club_name' => $this->club_name,
            'bio' => $this->bio,
            'city' => $this->city,
            'county' => $this->county,
            'country' => $this->country,
            'country_short' => $this->country_short,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'preferred_style' => $this->preferred_style,
            'profile_picture' => $this->profile_picture,
            'members' => $this->users->count(),
            'events' => $this->when(sizeof($this->cyclingClubEvent->where('event_date', '>=', date('Y-m-d'))) > 0, $this->cyclingClubEvent->where('event_date', '>=', date('Y-m-d'))->toArray()),
        ];
    }
}
