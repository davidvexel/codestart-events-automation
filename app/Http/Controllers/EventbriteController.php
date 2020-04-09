<?php

namespace App\Http\Controllers;

use App\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
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
	    /**
	     * Initialize client
	     */
	    $client = new Client(
		    [
			    'base_uri' => 'https://www.eventbriteapi.com/v3/',
			    'headers' => [
				    'Authorization' => 'Bearer ' . env('EVENTBRITE_TOKEN')
			    ]
		    ]
	    );

	    $organization_id = env('EVENTBRITE_ORGANIZATION_ID');

	    try {
		    $venues = $client->get(sprintf( 'organizations/%s/venues', $organization_id));
	    } catch (\Exception $e) {
	    	throw new \Error('Unable to retrieve venues.');
	    }

	    $json = $venues->getBody()->getContents();

	    $json = json_decode( $json, TRUE );

	    $venues = $json['venues'];

	    /* List of timezones */
	    $timezones = [
		    'America/Metlakatla'  => "(GMT-09:00) America/Alaska",
		    'America/Anchorage'   => "(GMT-08:00) America/Anchorage",
		    'America/Los_Angeles' => "(GMT-07:00) America/Los_Angeles",
		    'America/Denver'      => "(GMT-06:00) America/Denver",
		    'America/Chicago'     => "(GMT-05:00) America/Chicago",
	    ];

	    return view('eventbrite', compact('timezones', 'venues'));
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

	    /**
	     * Create the dates
	     */
	    try {
		    $startDate = CarbonImmutable::createFromFormat(
			    'm/d/Y H:i A',
			    sprintf('%s %s:%s %s', $input['date'], $input['startTimeH'], $input['startTimeM'], $input['startTimeA'] ),
			    $input['timezone']
		    );
	    } catch (\Exception $e) {
		    throw new \Error("Unable to create start date " . $e->getMessage());
	    }

	    try {
		    $endDate = CarbonImmutable::createFromFormat(
			    'm/d/Y H:i A',
			    sprintf('%s %s:%s %s', $input['date'], $input['endTimeH'], $input['endTimeM'], $input['endTimeA'] ),
			    $input['timezone']
		    );
	    } catch (\Exception $e) {
		    throw new \Error("Unable to create end date " . $e->getMessage());
	    }

	    /**
	     * The organization ID
	     */
	    $organization_id = env('EVENTBRITE_ORGANIZATION_ID');

	    /**
	     * Validate the venues exists
	     */
		if (empty($input['venues'])) {
			return redirect()->back()->with(['error' => 'You need to select at least one venue.']);
		}

	    /**
	     * Loop the selected venues, get the lat and long
	     * to get the timezone
	     */
	    foreach ($input['venues'] as $venue) {
		    /**
		     * Read the values comma delimited
		     */
	    	$venueValues = explode(':', $venue);
	    	$venueId = $venueValues[0];
	    	$latitude = $venueValues[1];
	    	$longitude = $venueValues[2];
	    	$city = $venueValues[3];

		    $now = Carbon::now()->timestamp;

		    /**
		     * Get the Timezone
		     */
	    	$base_uri = 'https://maps.googleapis.com/maps/api/timezone/json';
	    	$url = $base_uri . '?location='.$latitude.','.$longitude.'&timestamp='.$now.'&key='.env('GOOGLE_TIMEZONE_KEY');
		    $client = new \GuzzleHttp\Client();

		    /**
		     * Timezones response
		     */
		    try {
			    $timezonesResponse = $client->get( $url )->getBody()->getContents();
		    } catch (\Exception $e) {
		    	throw new \Error('Unable to get timezone from google maps api.');
		    }


		    /*
			  JSON decodes the response
			*/
		    $timezonesResponse = json_decode( $timezonesResponse, TRUE );

		    if ($timezonesResponse['status'] === 'OK' && isset($timezonesResponse['timeZoneId'])) {
			    /**
			     * Initialize eventbrite client
			     */
			    $client = $this->initializeClient();

			    /**
			     * Build arguments
			     */
			    $args = [
				    'event.name.html'        => $input['name'] . ' - ' . $city,
				    'event.description.html' => $input['summary'],
				    'event.start.utc'        => $startDate->tz('UTC')->toIso8601ZuluString(),
				    'event.start.timezone'   => $timezonesResponse['timeZoneId'],
				    'event.end.utc'          => $endDate->tz('UTC')->toIso8601ZuluString(),
				    'event.end.timezone'     => $timezonesResponse['timeZoneId'],
				    'event.currency'         => 'USD',
				    'event.venue_id'         => $venueId,
				    'event.logo_id'          => '62366392', // hardcoded image
			    ];

			    /**
			     * Try to create the event
			     * 1. Create an event for each venue selected
			     */
			    try {
				    $event = $client->request( 'POST','organizations/' . $organization_id . '/events/',
					    [
						    'form_params' => $args
					    ]
				    );
			    } catch (\Exception $e) {
				    throw new \Exception($e->getResponse()->getBody()->getContents());
			    }

			    /**
			     * Decode the event response
			     */
			    $event = json_decode($event->getBody()->getContents(), TRUE);

			    /**
			     * Crete the Event Description
			     * @see https://www.eventbrite.com.br/platform/docs/event-description
			     */
			    $args = [
			    	'modules' => [
			    		[
			    			'type' => 'text',
						    'data' => [
						    	'body' => [
						    		'type' => 'text',
								    'text' => $input['description'],
								    'alignment' => 'left',
							    ],
						    ],
					    ],
				    ],
				    'publish' => true,
				    'purpose' => 'listing',
			    ];

			    try {
				    $client->request( 'POST','events/' . $event['id'] . '/structured_content/1/', [
					    'json' => $args,
				    ]);
			    } catch (\Exception $e) {
				    throw new \Exception($e->getResponse()->getBody()->getContents());
			    }

			    /**
			     * Setup args for ticket class
			     */
			    $args = [
					'ticket_class.name'           => 'General',
				    'ticket_class.quantity_total' => 50,
				    'ticket_class.free'           => true,
				    'ticket_class.has_pdf_ticket' => true,
				    'ticket_class.order_confirmation_message' => $input['order_confirmation_message'],
			    ];

			    /**
			     * 2. Create a ticket class per each event
			     */
			    try {
			    	$client->request('POST', 'events/'. $event['id'] . '/ticket_classes/',
					    [
						    'form_params' => $args
					    ]
				    );
			    } catch (\Exception $e) {
			    	throw new \Error('Unable to create ticket class.');
			    }

			    /*
			     * 3. Publish the event
			     */
			    try {
			    	$client->request('POST', 'events/'. $event['id'] . '/publish/', []);
			    } catch (\Exception $e) {
				    throw new \Error('Unable to publish event.');
			    }
		    }

	    } // end of loop

	    return redirect()->back()->with(['success' => 'Events created!']);
    }

	/**
	 * Initialize client
	 *
	 * @return Client
	 */
    public function initializeClient()
    {
    	try {
		    return new Client(
			    [
				    'base_uri' => 'https://www.eventbriteapi.com/v3/',
				    'headers' => [
					    'Authorization' => 'Bearer ' . env('EVENTBRITE_TOKEN'),
					    'Accept'        => 'application/json',
				    ]
			    ]
		    );
	    } catch (\Exception $e) {
    		throw new \Error('Unable to initialize eventbrite client.');
	    }
    }
}
