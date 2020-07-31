<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClubEvent extends Model
{
    /**
     * User Profile Table
     */
    protected $table = "club_event";

    /**
     * Attributes which are assignable
     * 
     * @var array
     */
    protected $fillable = [
        'admin_id', 'cycling_club_id', 'event_name', 'description', 'event_date', 'start_time', 'start_address', 'city', 'county', 'country', 'country_short', 'lat', 'lng', 'event_picture'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'cycling_club_id', 'admin_id', 'created_at', 'updated_at', 'pivot'
    ];

    /**
     * Gets The Cycling Club belonging to the event
     */
    public function cyclingClub()
    {
        return $this->belongsTo('App\CyclingClub');
    }

    public function attendees()
    {
        return $this->belongsToMany('App\User', 'user_club_event')->withTimestamps();
    }
}
