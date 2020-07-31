<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'created_at', 'updated_at', 'pivot'
    ];

    /**
     * Disables timestamps
     */
    public $timestamps = false;

    /**
     * User has a Profile
     */
    public function userProfile()
    {
        return $this->hasOne('App\UserProfile');
    }

    /**
     * User is an Admin of Cycling Club
     */
    public function cyclingClubAdmin()
    {
        return $this->hasMany('App\CyclingClub');
    }

    /**
     * User is an Member of Cycling Club
     */
    public function cyclingClubMember()
    {
        return $this->belongsToMany('App\CyclingClub', 'user_cycling_club')->withTimestamps();
    }

    /**
     * User is an Member of Cycling Club
     */
    public function clubEventAttendee()
    {
        return $this->belongsToMany('App\ClubEvent', 'user_club_event')->withTimestamps();
    }
}
