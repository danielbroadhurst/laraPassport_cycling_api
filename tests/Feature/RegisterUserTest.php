<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

use App\User;
use Laravel\Passport\Passport;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    public function testApiRegisterUser()
    {
        $response = $this->postJson('/api/register', ['firstName' => 'Sally', 'lastName' => 'Tripp', 'email' => 'test@email.com', 'password' => 'password']);
        $this->assertDatabaseHas('users', [
            'email' => 'test@email.com',
        ]);
        $response
        ->assertStatus(201);
    }

    public function testApiLoginGuzzle()
    {
        // Create a mock and queue response with sample data which is returned from oAuth Login Endpoint.
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{
                "token_type": "Bearer",
                "expires_in": 31536000,
                "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIyIiwianRpIjoiMTM3NzBiZDU5Y2I0OWIzNGMzYWNkNDhmZjZhOWFiMTBkZjI4ZDhlMGFhNDY2OGFhYzI1NWMxYThjMmI2ZjhlN2NhMzNjMjkxZTAwZGNjZDEiLCJpYXQiOjE1ODc4NDAwOTgsIm5iZiI6MTU4Nzg0MDA5OCwiZXhwIjoxNjE5Mzc2MDk4LCJzdWIiOiI4Iiwic2NvcGVzIjpbXX0.y8lVtpY2FMJNpJ5BKW4zODA0WmaWcBGW-__0G2wDZ4YgsAqIaNqigmqXWCIgLR5Ghi6CLiMCKVGEPikygIAfKb_rUhxLE21C9GE2MUfaOnstxlwGAl4qh4cSfhjTAR6NpXzkGsJ-Bh5Ph8R2WMSKaWEmpb6eIlDNnPSL8idh3rLcmQd-mXsqY3AxSj33d23rmMtjPRpKb8UST_F4dlFbaRYumTLhYQ4RJY_xerqMT79Eju7fxqrBr2avHsg1Y8t0rbeqDd5-F7Keq9QM8psrpSl_8lSdaz8PybWLHyLvOYzwCiNN4oI0Kmy1JxNfgkSY5S4kd1BlhH8dmlgeirlTGhL_G1iSXxeSYwKyN3ZZB8bmmcDAuV9iDPbwmm5ItC4XRhMLV3UlWvV7bVocK3Hr_QnyEd-2piaCavNdURbBif15fhFFGMrhSCTDQuzDxGvrhrQK0GRJWnj4tnWHTqOZ5phLUadhoLnMA_P0_SEUZV_sGfw5HPfrfc3KtLIMHju2nWL9nF5my8PDp5_11AzuAdM6xyrB2hm-E1vn6zDoSkfKidpiXNJvDDjJlW17poTXpqeWgc1E1_BhAyVXguatMVRfWDFIeBneDloOA4XIjPI3q8SAFdFHB8rnoeYGEilpfcSHSZ3d9R4vuQcq3-5mgwJUzin54pCsCLFHdtx4xOE",
                "refresh_token": "def502009de7f4340d1c13a39095aa6fa87ba4245aca719e8663cda54459ae7d760df162ff309dbff57a5c326883904e983ea7a30aa870336bc0e0cb45982641fa59623c1d1e3a6a449a3f349df887c2285e32a777ad190632b2c00b54be11f6ff943104417a56dcfdaad0c9a12925b0821c980b20988e72db57102c5d4bae11e3a082fc8c40b6e2d01c97daa563af11de1956b3dcbcaeea9e3a1838a38f1a875f9a65cde6463ef5e392805a2eb6737434f86bb13cf2ca20e4befaca8fcbdd406b633202999ef85f2eedb8733771f83833478e1639aef429ddba1e7d4edc06512f6508a2401460741ae82065af8c0f2007209dab6a1bcda0170082542ef017f30b37affe5a7e53fc7b95b266559da2c3f92b686686146d564ba79c000794ae11d9b31f0690221a70318d84dc10292107ce1c6d5f05498090fd451231cb1f6cd8c8abb015d8c8e5efbb068f66a7e2e63cf3beac9b35ae475d383a2a33dc88a25cd0"
            }'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $response = $client->request('POST', '/api/login', ['email' => 'dj@enail.com', 'password' => 'password']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApiLogout() {
        Passport::actingAs(
            factory(User::class)->create()
        );
        $response = $this->post('/api/logout');
        $response->assertStatus(200);
    }

    public function testApiDeleteUser() {
        $user = Passport::actingAs(
            factory(User::class)->create(),
        );
        $this->assertDatabaseHas('users', [
            'email' => $user->email,
        ]);
        $response = $this->delete('/api/delete-account/'.$user->id);
        $this->assertDatabaseMissing('users', [
            'email' => $user->email,
        ]);
        $response->assertStatus(200);
    }

    public function testApiUserErrorUnauthenticated()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }
}
