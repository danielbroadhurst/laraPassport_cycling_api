<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    /**
     * User Profile Table
     */
    protected $table = "user_profile";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'gender', 'date_of_birth', 'town', 'region', 'country', 'current_bike', 'preferred_style', 'profile_picture', 'bio'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',' user_id', 'is_admin', 'created_at', 'updated_at'
    ];

    /**
     * Gets The User belonging to the profile
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
