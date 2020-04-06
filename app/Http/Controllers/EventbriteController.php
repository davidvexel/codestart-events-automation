<?php

namespace App\Http\Controllers;

use App\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use \DrewM\MailChimp\MailChimp;

class EventbriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

	    return view('eventbrite', compact('timezones'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param $request Request
     *
     * @throws
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $input = $request->except('_token');

        dd($input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Event $event)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function destroy(Event $event)
    {
        //
    }
}
