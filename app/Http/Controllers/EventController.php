<?php

namespace App\Http\Controllers;

use App\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use \DrewM\MailChimp\MailChimp;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

        // Input name of the event
        $name = $input['name'];

	    /**
	     * Try to create the date
	     */
        try {
	        $date = CarbonImmutable::createFromFormat(
		        'm/d/Y H:i A',
		        sprintf('%s %s:%s %s', $input['date'], $input['timeH'], $input['timeM'], $input['timeA'] ),
		        $input['timezone']
	        );
        } catch (\Exception $e) {
        	throw new \Error("Unable to create date " . $e->getMessage());
        }

        // human readable format
        $nameAndDate = sprintf('%s - %s', $name, $date->toDayDateTimeString());

        // Date UTC
	    $dateUTC = $date->tz('UTC');

        if ( empty( $name ) || empty( $date ) ) {
        	return redirect()->back()->with( ['error' => 'Missing name or date'] );
        }

	    /**
	     * Try to initialize the MailChimp service
	     */
        try {
			$mailchimp = new Mailchimp(env('MAILCHIMP_KEY'));
			/* Fix */
			$mailchimp->verify_ssl = false;
        } catch (\Exception $e) {
        	throw new \Error('Unable to initiate MailChimp service. ' . $e->getMessage());
        }

		// 1. Create new MailChimp audience list with name of the event and date
	    $list = $mailchimp->post('lists', [
	    	'name' => $nameAndDate ,
		    'contact' => [
		    	'company' => 'Codestart Academy',
			    'address1' => 'Address 1',
			    'address2' => 'Address 2',
			    'city' => 'City',
			    'state' => 'State',
			    'zip' => '77720',
			    'country' => 'Mexico',
			    'phone' => '556173278349',
		    ],
		    'permission_reminder' => 'You signed up for this stuff',
		    'campaign_defaults' => [
		    	'from_name' => 'Codestart Academy',
			    'from_email' => 'no-reply@codestartacademy.com',
			    'subject' => 'Webinar Reminder',
			    'language' => 'english',
		    ],
		    'email_type_option' => false,
	    ]);


		/*
         * Validate this step worked
         */
		if ( empty( $list ) || ! isset( $list['id'] ) ) {
			return redirect()->back()->with(['error' => 'Unable to create the MailChimp audience.']);
		}

		// 2. Create new templates folder
		$templatesFolder = $mailchimp->post('/template-folders',
			[
				'name' => $nameAndDate
			]
		);

		if ( ! isset( $templatesFolder['id'] ) || empty( $templatesFolder['id'] ) ) {
			return redirect()->back()->with(['error' => 'Unable to create templates folder.']);
		}

	    // 3. Create new campaigns folder
	    $campaignsFolder = $mailchimp->post('/campaign-folders',
		    [
		    	'name' => $nameAndDate,
		    ]
	    );

	    if ( ! isset( $campaignsFolder['id'] ) || empty( $campaignsFolder['id'] ) ) {
		    return redirect()->back()->with(['error' => 'Unable to create campaigns folder.']);
	    }

		// Setup the required emails
	    $emails = [
	    	[
	    		'key' => 'welcome',
			    'title' => 'Webinar Welcome Email',
			    'subject' => 'You are confirmed for our Webinar',
		    ],
		    [
		    	'key' => 'weekBefore',
			    'title' => 'Webinar One Week Reminder',
			    'subject' => 'Our Webinar is in one week',
			    'scheduledTo' => $dateUTC->subDays(7),
		    ],
		    [
			    'key' => 'dayBefore',
			    'title' => 'Webinar One Day Reminder',
			    'subject' => 'Our Webinar is Tomorrow',
			    'scheduledTo' => $dateUTC->subHours(32),
		    ],
		    [
		    	'key' => 'sameDay',
			    'title' => 'Webinar Same Day Reminder',
			    'subject' => 'Our Webinar is Today',
			    'scheduledTo' => $dateUTC->subHours(10),
		    ],
		    [
			    'key' => 'hourBefore',
			    'title' => 'Webinar Hour Before Reminder',
			    'subject' => 'Our Webinar is Starting in One Hour',
			    'scheduledTo' => $dateUTC->subHours(1),
		    ],
		    [
			    'key' => 'followUpAttended',
			    'title' => 'Follow Up',
			    'subject' => 'Our Webinar Follow Up',
			    'scheduledTo' => $dateUTC->addDays(1),
		    ]
	    ];

	    // 4. Create new templates using HTML
		// Loop the required emails and create templates
		foreach ( $emails as $email ) {
			// Get the HTML template and replace the date.
			$html = view(
				'emails.mailchimp-templates.'.$email['key'],
				compact('name', 'date')
			)->render();

			// Create the template for the email
			$template = $mailchimp->post('/templates',
				[
					'name' => $email['title'],
					'folder_id' => $templatesFolder['id'],
					'html' => $html,
				]
			);

			// 5. Create new campaigns for that email using the template
			if ( isset( $template['id'] ) ) {
				// Create a new campaign
				$campaign = $mailchimp->post('/campaigns',
					[
						'type' => 'regular',
						'recipients' => [
							'list_id' => $list['id'],
						],
						'settings' => [
							'subject_line' => $email['subject'],
							'preview_text' => '',
							'title'        => $email['title'],
							'folder_id'    => $campaignsFolder['id'],
							'template_id'  => $template['id'],
						]
					]
				);

				// Schedule the campaign
				if ($campaign && isset( $campaign['id'] ) && isset($template['scheduledTo'])) {
					$mailchimp->post(sprintf('/campaigns/%/actions/schedule'),
						[
							'schedule_time' => $email['scheduledTo']->toIso8601String()
						]
					);
				}
			}
		}

		return redirect()->back()->with(['success' => 'Audience, Templates and Campaigns has been created successfully.']);

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
