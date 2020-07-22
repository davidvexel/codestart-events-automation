<?php

namespace App\Http\Controllers;

use App\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use \DrewM\MailChimp\MailChimp;
use PhpParser\Node\Expr\Array_;

class MailChimpController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$folders = [];

		$mailchimp = $this->mailchimp();

		$campaignFolders = $mailchimp->get('/campaign-folders', ['count' => '100']);

		if (isset($campaignFolders['folders']) && !empty($campaignFolders['folders'])) {
			$folders = $campaignFolders['folders'];
		}

		return view('mailchimp.selectFolder', compact('folders'));
	}

	/**
	 * Form to create new campaigns
	 *
	 * @param $folderId
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function createNewCampaigns($folderId) {
		$mailchimp = $this->mailchimp();
		$campaigns = [];

		$response = $mailchimp->get('/campaigns', ['folder_id' => $folderId]);

		if (isset($response['campaigns']) && !empty($response['campaigns'])) {
			$campaigns = $response['campaigns'];
		}

		/* List of timezones */
		$timezones = [
			'US/Alaska' => "(GMT-09:00) Alaska",
			'US/Pacific' => "(GMT-08:00) Pacific Time",
			'US/Mountain' => "(GMT-07:00) Mountain Time",
			'US/Central' => "(GMT-06:00) Central Time",
			'US/Eastern' => "(GMT-05:00) Eastern Time",
		];

		return view('mailchimp.create', compact('timezones', 'campaigns'));
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
		// Get input except by the token
		$input = $request->except('_token');

		// Input name of the event
		$name = $input['name'];

		// validate date
		if (empty($input['date'])) {
			return redirect()->back()->with(['error' => 'Please select a date']);
		}

		// Parse the date
		$date = $this->parseDateAndTime($input);

		// Validate name
		if (empty($name) || empty($date)) {
			return redirect()->back()->with(['error' => 'Missing name or date']);
		}

		// human readable format
		$nameAndDate = sprintf('%s - %s', $name, $date->toDayDateTimeString());

		// Initialize mailchimp
		$mailchimp = $this->mailchimp();

		// 1. Create new MailChimp audience list with name of the event and date
		$list = $this->createAudience($mailchimp, $nameAndDate);

		/*
         * Validate this step worked
         */
		if (empty($list) || !isset($list['id'])) {
			return redirect()->back()->with(['error' => 'Unable to create the MailChimp audience.']);
		}

		// Subscribe Dan's email into this new audience
		$subscription = $this->subscribeMemberToNewList($mailchimp, $list);

		if (!isset($subscription['id']) || empty($subscription['id'])) {
			return redirect()->back()->with(['error' => 'Unable to subscribe email to new audience.']);
		}

		// Create the automation
		$this->createAutomation($mailchimp, $list);

		// 2. Create new templates folder
		$templatesFolder = $this->createTemplatesFolder($mailchimp, $nameAndDate);

		if (!isset($templatesFolder['id']) || empty($templatesFolder['id'])) {
			return redirect()->back()->with(['error' => 'Unable to create templates folder.']);
		}

		// 3. Create new campaigns folder
		$campaignsFolder = $this->createCampaignsFolder($mailchimp, $nameAndDate);

		if (!isset($campaignsFolder['id']) || empty($campaignsFolder['id'])) {
			return redirect()->back()->with(['error' => 'Unable to create campaigns folder.']);
		}

		// Setup the required emails
		$emails = $this->listOfEmailsToCreate($input, $date);

		// 4. Create new templates using HTML
		// Loop the required emails and create templates
		foreach ($emails as $email) {
			/**
			 * Get the HTML content of the campaign to create the new one
			 * @see https://mailchimp.com/developer/reference/campaigns/campaign-content/
			 */
			$response = $mailchimp->get('campaigns/'. $email['id'] . '/content');
			$html = '<html>Content of the template</html>';

			if ($response['html']) {
				$html = $response['html'];
			}

			/**
			 * Find and replace the custom fields in the template
			 */
			foreach($input['customFieldKeys'] as $index => $customFieldKey) {
				if ( !empty($customFieldKey) && !empty($input['customFieldValues'][$index]) ) {
					$html = str_replace($customFieldKey, $input['customFieldValues'][$index], $html);
				}
			}

			// Create the template for the email
			$template = $mailchimp->post('/templates',
				[
					'name' => $email['title'],
					'folder_id' => $templatesFolder['id'],
					'html' => $html,
				]
			);

			// 5. Create new campaigns for that email using the template
			if (isset($template['id'])) {
				// Create a new campaign
				$campaign = $mailchimp->post('/campaigns',
					[
						'type' => 'regular',
						'recipients' => [
							'list_id' => $list['id'],
						],
						'settings' => [
							'subject_line' => $email['title'],
							'preview_text' => '',
							'title' => $email['title'],
							'folder_id' => $campaignsFolder['id'],
							'template_id' => $template['id'],
							'from_name' => 'Codestart Academy',
							'reply_to' => 'info@codestartacademy.com',
						]
					]
				);

				// Schedule the campaign
				if ($campaign && isset($campaign['id']) && isset($email['scheduledTo'])) {
					$mailchimp->post(sprintf('/campaigns/%s/actions/schedule', $campaign['id']),
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
	 * Initialize the mailchimp api
	 *
	 * @return MailChimp
	 */
	public function mailchimp() {
		/**
		 * Try to initialize the MailChimp service
		 */
		try {
			$mailchimp = new Mailchimp(env('MAILCHIMP_KEY'));
			/* Fix */
			$mailchimp->verify_ssl = false;
			return $mailchimp;
		} catch (\Exception $e) {
			throw new \Error('Unable to initiate MailChimp service. ' . $e->getMessage());
		}
	}

	/**
	 * Parse date and time
	 *
	 * @param $input
	 * @return CarbonImmutable
	 */
	public function parseDateAndTime($input) {
		/**
		 * Try to create the date
		 */
		try {
			return CarbonImmutable::createFromFormat(
				'm/d/Y H:i A',
				sprintf('%s %s:%s %s', $input['date'], $input['timeH'], $input['timeM'], $input['timeA']),
				$input['timezone']
			);
		} catch (\Exception $e) {
			throw new \Error("Unable to create date " . $e->getMessage());
		}
	}

	/**
	 * Create the mailchimp audience
	 *
	 * @param $mailchimp
	 * @param $nameAndDate
	 * @return mixed
	 */
	public function createAudience($mailchimp, $nameAndDate) {
		return $mailchimp->post('lists', [
			'name' => $nameAndDate,
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
	}

	/**
	 * Subscribe member
	 *
	 * @param $mailchimp
	 * @param $list
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function subscribeMemberToNewList($mailchimp, $list) {
		try {
			return $mailchimp->post('lists/'.$list['id'].'/members',
				[
					'email_address' => 'dmk354@nyu.edu',
					'status' => 'subscribed',
				]
			);
		} catch (\Exception $e) {
			return redirect()->back()->with(['error' => 'Unable to subscribe email to new audience.']);
		}
	}

	/**
	 * Create the folder
	 *
	 * @param $mailchimp
	 * @param $nameAndDate
	 * @return mixed
	 */
	public function createTemplatesFolder($mailchimp, $nameAndDate) {
		return $mailchimp->post('/template-folders',
			[
				'name' => $nameAndDate
			]
		);
	}

	/**
	 * Create the campaigns folder
	 *
	 * @param $mailchimp
	 * @param $nameAndDate
	 *
	 * @return object $response
	 */
	public function createCampaignsFolder($mailchimp, $nameAndDate) {
		try {
			$response = $mailchimp->post('/campaign-folders',
				[
					'name' => $nameAndDate,
				]
			);
		} catch (\Exception $e) {
			throw new \Error($e->getMessage());
		}
		return $response;
	}

	/**
	 * Determine the emails to create based on the original campaigns
	 *
	 * @param $input
	 * @param $date
	 *
	 * @return array $campaigns
	 */
	public function listOfEmailsToCreate($input, $date) {

		$campaigns = [];
		// Define the email campaigns based on the selected scheduleCampaignIds
		foreach ($input['scheduleCampaignIds'] as $key => $campaignId) {
			$campaigns[$campaignId]['id'] = $campaignId;
			$numberOfHoursOrDays = (int) $input['scheduleCampaignsNumberOfHoursOrDays'][$campaignId];

			// date of the event to UTC
			$dateUTC = $date->tz('UTC');

			if ( $input['scheduleCampaignBeforeOrAfter'][$campaignId] === 'before' ) {
				if ($input['scheduleCampaignHoursOrDays'][$campaignId] === 'hours') {
					$scheduledTo = $dateUTC->subHours($numberOfHoursOrDays);
				} else {
					$scheduledTo = $dateUTC->subDays($numberOfHoursOrDays);
				}
			} else {
				// if after
				if ($input['scheduleCampaignHoursOrDays'][$campaignId] === 'hours') {
					$scheduledTo = $dateUTC->addHours($numberOfHoursOrDays);
				} else {
					$scheduledTo = $dateUTC->addDays($numberOfHoursOrDays);
				}
			}

			// Add scheduled to value
			$campaigns[$campaignId]['scheduledTo'] = $scheduledTo;

			// Add the campaign title
			$campaigns[$campaignId]['title'] = $input['scheduleCampaignTitle'][$campaignId];
		}

		return $campaigns;
	}

	/**
	 * Create the automation with a single welcome email
	 *
	 * @param $mailchimp
	 * @param $list
	 */
	public function createAutomation($mailchimp, $list)
	{
		$automation = $mailchimp->post('/automations',
			[
				'recipients' => [
					'list_id' => $list['id'],
				],
				'settings' => [
					'from_name' => 'Codestart Academy',
					'reply_to' => 'info@codestartacademy.com',
				],
				'trigger_settings' => [
					'workflow_type' => 'singleWelcome',
				],
			]
		);

		return $automation;
	}
}
