<?php
namespace App\Helpers;

class Maps
{
    public function searchMaps($query)
    {
        $http = new \GuzzleHttp\Client;
        try {
            $response = $http->get(config('services.here.geocode_endpoint'), [
                'query' => [
                    'searchtext' => "$query->city $query->country",
                    'app_id' => config('services.here.app_id'),
                    'app_code' => config('services.here.app_code'),
                    'gen' => 8
                ]
            ]);
            $response = $response->getBody()->getContents();
            $country = strpos($response, $query->country);
            $city = strpos($response, $query->city);
            if ($country > 0 && $city > 0) {
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