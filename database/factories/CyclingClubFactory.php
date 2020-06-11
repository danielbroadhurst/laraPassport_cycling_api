<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\CyclingClub;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(CyclingClub::class, function (Faker $faker) {
    return [
        'user_id' => factory('App\User')->create()->id,
        'club_name' => $faker->text(45),
        'bio' => $faker->text(),
        'town' => $faker->citySuffix,
        'region' => $faker->city,
        'country' => $faker->country,
        'preferred_style' => $faker->text(),
        'profile_picture' => $faker->url,
        'is_active' => true,
    ];
});
