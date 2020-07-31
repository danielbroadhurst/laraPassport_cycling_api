<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CyclingClub extends Model
{
    /**
     * User Profile Table
     */
    protected $table = "cycling_club";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'club_name', 'bio', 'city', 'county', 'country', 'country_short', 'lng', 'lat', 'preferred_style', 'profile_picture'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'user_id', 'created_at', 'updated_at', 'is_active', 'pivot'
    ];

    /**
     * Gets The User belonging to the profile
     */
    public function userAdmin()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * Cycling Club has events
     */
    public function cyclingClubEvent()
    {
        return $this->hasMany('App\ClubEvent');
    }

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_cycling_club')->withTimestamps();
    }
}
