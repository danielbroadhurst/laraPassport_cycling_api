<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClubEvent extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $attendees = $this->attendees->map(function ($item, $key){
            $array = $item->userProfile->makeHidden('date_of_birth', 'gender', 'bio', 'country');
            $array['user_name'] = $item->first_name . ' ' . $item->last_name;
            return $array;
        });
        return [
            'id' => $this->id,
            'event_name' => $this->event_name,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'event_date' => $this->event_date,
            'start_time' => $this->start_time,
            'start_address' => $this->start_address,
            'city' => $this->city,
            'county' => $this->county,
            'country' => $this->country,
            'country_short' => $this->country_short,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'profile_picture' => $this->profile_picture,
            'cycling_club' => $this->cyclingClub,
            'map_array' => $this->map_array,
            'elevation_array' => $this->elevation_array,
            'attendees' => $attendees
        ];
    }
}
