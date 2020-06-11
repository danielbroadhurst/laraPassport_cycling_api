<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\User;
use Laravel\Passport\Passport;
use App\UserProfile;

use Illuminate\Http\UploadedFile;

class CreateUserProfileTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testUserHasAProfile()
    {
        $user = factory(User::class)->create();
        $profile = factory(UserProfile::class)->create(['user_id' => $user->id]);
        // Method 1:
        $this->assertInstanceOf(UserProfile::class, $user->userProfile); 
        
        // Method 2:
        $this->assertEquals(1, $user->userProfile->count());
    }

    public function testApiUserCanCreateAProfile() {
        Passport::actingAs(
            factory(User::class)->create()
        ); 
        $fakeProfile = array(
            'gender' => 'male',
            'date_of_birth' => '2020-07-17',
            'bio' => 'This would be the Bio',
            'town' => 'Bolton',
            'region' => 'Greater Manchester',
            'country' => 'United Kingdom',
            'current_bike' => 'Planet X',
            'preferred_style' => 'Road Bike',
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg')
        );
        $response = $this->post('api/user-profile', $fakeProfile);
        $response
        ->assertStatus(201)
        ->assertJsonFragment($fakeProfile);
    }

    public function testApiUserCanUpdateProfile()
    {
        $user = factory(User::class)->create();
        $profile = factory(UserProfile::class)->create(['user_id' => $user->id]);
        Passport::actingAs(
            $user
        ); 
        $updatedData = array(
            'gender' => 'male',
            'country' => 'United Kingdom',
            'bio' => 'This is an updated bio.'
        );
        $response = $this->put('api/user-profile/'.$user->id, $updatedData);
        $response
        ->assertStatus(202)
        ->assertJsonFragment($updatedData);
    }

    public function testApiReturnsUserWithProfile()
    {
        $user = factory(User::class)->create();
        factory(UserProfile::class)->create(['user_id' => $user->id]);
        Passport::actingAs(
            $user
        ); 
        $response = $this->get('api/user/'.$user->id);
        //dd($response);
        $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'first_name' => $user->first_name
        ])
        ->assertJsonFragment([
            'bio' => $user->userProfile->bio
        ]);
    }
}
