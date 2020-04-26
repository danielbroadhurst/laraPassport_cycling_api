<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\UserProfile;
use Faker\Generator as Faker;

$factory->define(UserProfile::class, function (Faker $faker) {
    return [
        'user_id' => factory('App\User')->create()->id,
        'gender' => 'male',
        'date_of_birth' => $faker->dateTimeThisCentury(),
        'bio' => $faker->text(),
        'town' => $faker->citySuffix,
        'region' => $faker->city,
        'country' => $faker->country,
        'current_bike' => $faker->text(),
        'preferred_style' => $faker->text(),
        'profile_picture' => $faker->url,
        'is_admin' => $faker->boolean,
    ];
});
