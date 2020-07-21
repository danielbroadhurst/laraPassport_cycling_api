<?php
namespace App\Helpers;

class Maps
{
    /**
     * Calls Geocoder API and retrieves location information.
     * Required fields are club_name, city, country.
     *
     * @param $query Object containing city and country
     */
    public function searchMaps($query)
    {
        // Create Guzzle client to make HTTP call.
        $http = new \GuzzleHttp\Client;
        // Try call or catch the error and return error response.
        try {
            // Call Geocoder API Endpoint
            $response = $http->get(config('services.here.geocode_endpoint'), [
                'query' => [
                    'searchtext' => "$query->city $query->country",
                    'apiKey' => config('services.here.api_key'),
                    'gen' => 8
                ]
            ]);
            // Get the contents of the response
            $response = $response->getBody()->getContents();
            // Check that the country and city in reponse matches query.
            $country = strpos($response, $query->country);
            $city = strpos($response, $query->city);
            if ($country > 0 && $city > 0) {
                // Decode JSON response and return location information.
                $json = json_decode($response);
                $location = $json->Response->View[0]->Result[0]->Location;
                return $location;
            } else {
                return false;
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return response()->json('Something went wrong on the server.', $e->getCode());
        }
    }
}