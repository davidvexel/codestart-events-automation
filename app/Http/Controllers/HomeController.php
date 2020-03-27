<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
    	/* List of timezones */
    	$timezones = [
		    'US/Alaska'   => "(GMT-09:00) Alaska",
		    'US/Pacific'  => "(GMT-08:00) Pacific Time",
		    'US/Mountain' => "(GMT-07:00) Mountain Time",
		    'US/Central'  => "(GMT-06:00) Central Time",
		    'US/Eastern'  => "(GMT-05:00) Eastern Time",
	    ];

        return view('home', compact('timezones'));
    }
}
