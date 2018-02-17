<?php
require_once __DIR__ . '/vendor/autoload.php';

session_start();

define('APPLICATION_NAME', 'Google Calendar API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/.credentials/calendar-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/calendar-php-quickstart.json
define('SCOPES', implode(' ', array(
    Google_Service_Calendar::CALENDAR,
    Google_Service_Calendar::CALENDAR_READONLY

)));

/*
 * if (php_sapi_name() != 'cli') {
 * throw new Exception('This application must be run on the command line.');
 * }
 */

/**
 * Returns an authorized API client.
 * 
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');
    $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/calendar/index.php');
    
    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        if(isset($_GET['code']) and trim($_GET['code']) != ''){
            $authCode = isset($_GET['code']) ? $_GET['code'] : '';
            //   $authCode = trim(fgets(STDIN));
            
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            
            // Store the credentials to disk.
            if (! file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0775, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            //printf("Open the following link in your browser:\n%s\n", $authUrl);
            header('Location: '.$authUrl);
        }       
    }
    $client->setAccessToken($accessToken);
    
    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * 
 * @param string $path
 *            the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path)
{
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

$event = new Google_Service_Calendar_Event(array(
    'summary' => 'Happy birthday',
    'location' => '226 SAS Nagar Mohali Punjab',
    'description' => 'my Birthday',
    'start' => array(
        'dateTime' => '2018-03-23T10:26:00-07:00',
        'timeZone' => 'America/Los_Angeles'
    ),
    'end' => array(
        'dateTime' => '2018-03-23T22:26:00-07:00',
        'timeZone' => 'America/Los_Angeles'
    ),
    'recurrence' => array(
        'RRULE:FREQ=DAILY;COUNT=2'
    ),
    'attendees' => array(
        array(
            'email' => 'lpage@example.com'
        ),
        array(
            'email' => 'manish.1986200821@gmail.com'
        )
    ),
    'reminders' => array(
        'useDefault' => FALSE,
        'overrides' => array(
            array(
                'method' => 'email',
                'minutes' => 24 * 60
            ),
            array(
                'method' => 'popup',
                'minutes' => 10
            )
        )
    )
));

/* $calendarId = 'primary';
$event = $service->events->insert($calendarId, $event); */
//printf('Event created: %s\n', $event->htmlLink);
// Print the next 10 events on the user's calendar.
 $calendarId = 'primary';
$optParams = array(
    'maxResults' => 10,
    'orderBy' => 'startTime',
    'singleEvents' => TRUE,
    'timeMin' => date('c'),
);
$results = $service->events->listEvents($calendarId, $optParams);

if (count($results->getItems()) == 0) {
    print "No upcoming events found.\n";
} else {
    print "Upcoming events:\n";
    foreach ($results->getItems() as $event) {
        $start = $event->start->dateTime;
        if (empty($start)) {
            $start = $event->start->date;
        }
        printf("%s (%s)\n", $event->getSummary(), $start);
    } 
}