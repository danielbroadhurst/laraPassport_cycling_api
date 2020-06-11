<?php

namespace App\Http\Controllers;

use PragmaRX\Countries\Package\Countries;

class CountriesController extends Controller
{
    public function index()
    {
        $countries = new Countries();
        $countries = $countries->all()->pluck('name.common')->toArray();
        asort($countries);
        return response()->json(array_values($countries));
    }
}
