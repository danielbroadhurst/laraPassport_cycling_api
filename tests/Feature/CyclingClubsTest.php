<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\User;
use Laravel\Passport\Passport;
use App\UserProfile;
use App\Helpers\Maps;
use App\CyclingClub;

use Illuminate\Http\UploadedFile;

class CyclingClubsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function testUserHasACyclingClub()
    {
        $user = factory(User::class)->create();
        factory(UserProfile::class)->create(['user_id' => $user->id]);
        factory(CyclingClub::class)->create(['user_id' => $user->id]);
        // Method 1:
        $this->assertInstanceOf(CyclingClub::class, $user->cyclingClubAdmin[0]); 
        
        // Method 2:
        $this->assertEquals(1, $user->cyclingClubAdmin->count());
    }

    public function testApiUserCanCreateACyclingClub() {
        Passport::actingAs(
            $user = factory(User::class)->create(),
            factory(UserProfile::class)->create(['user_id' => $user->id])
        ); 
        $fakeCyclingClub = array(
            'club_name' => 'A Cycling Club',
            'bio' => 'This would be the Bio',
            'town' => 'Bolton',
            'region' => 'Greater Manchester',
            'country' => 'United Kingdom',
            'preferred_style' => 'Road',
            'profile_picture' => UploadedFile::fake()->image('avatar.jpg')
        );
        $response = $this->post('api/cycling-club', $fakeCyclingClub);
        $response
        ->assertStatus(201)
        ->assertJsonFragment($fakeCyclingClub);
    }

    public function testApiUserCanUpdateProfile()
    {
        $user = factory(User::class)->create();
        factory(UserProfile::class)->create(['user_id' => $user->id]);
        factory(CyclingClub::class)->create(['user_id' => $user->id]);
        Passport::actingAs(
            $user
        ); 
        $updatedData = array(
            'bio' => 'This is an updated bio.'
        );
        
        $response = $this->put('api/cycling-club/'.$user->cyclingClubAdmin[0]->id, $updatedData);
        $response
        ->assertStatus(202)
        ->assertJson([[
            'cycling_club_admin' => [array(
                'bio' => 'This is an updated bio.'
            )],
        ]]);
    }

    public function testApiReturnsUserWithCyclingClubAdmin()
    {
        $user = factory(User::class)->create();
        factory(UserProfile::class)->create(['user_id' => $user->id]);
        factory(CyclingClub::class)->create(['user_id' => $user->id]);
        Passport::actingAs(
            $user
        ); 
        $response = $this->get('api/user/');
        $response
        ->assertStatus(200)
        ->assertJsonFragment([
            'first_name' => $user->first_name
        ])
        ->assertJson([[
            'cycling_club_admin' => [array(
                'bio' => $user->cyclingClubAdmin[0]->bio
            )],
        ]]);
    }
}
