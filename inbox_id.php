<?php
require './vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->setScopes(Google_Service_Gmail::GMAIL_READONLY);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

// Print the labels in the user's account.
$userId = 'me';
//$results = $service->users_labels->listUsersLabels($user);

print_r(listMessages($service,$userId));

function listMessages($service, $userId) {
    $pageToken = NULL;
    $messages = array();
    $opt_param = array();
    do {
      try {
        if ($pageToken) {
          $opt_param['pageToken'] = $pageToken;
        }
        $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
        if ($messagesResponse->getMessages()) {
          $messages = array_merge($messages, $messagesResponse->getMessages());
          $pageToken = $messagesResponse->getNextPageToken();
        }
      } catch (Exception $e) {
        print 'An error occurred: ' . $e->getMessage();
      }
    } while ($pageToken);
  
    // foreach ($messages as $message) {
    //     $optParamsGet2['format'] = 'full';
    //     $single_message = $service->users_messages->get('me', $message->getId(), $optParamsGet2);
    //     $threadId = $single_message->getThreadId();
    //     $payload  = $single_message->getPayload();
    //     $headers  = $payload->getHeaders();
    //     $parts    = $payload->getParts();
    //     $body     = $parts[0]['body'];
    //     $rawData  = $body->data;
    //     $sanitizedData = strtr($rawData,'-_', '+/');
    //     $decodedMessage = base64_decode($sanitizedData); 
  
    //     print  'Message with ID: ' . $decodedMessage . '<br/>';
  
    // }
  
    return $messages;
  }


//   $messageId = "166ceffd371c93f3";

//  //==================Return message content=========================//
// print_r(getMessage($service,$userId,$messageId));

// function getMessage($service, $userId, $messageId) {
//     try {
//         $response = array();
//       $message = $service->users_messages->get($userId, $messageId);

//         $optParamsGet2['format'] = 'full';
//         $single_message = $service->users_messages->get('me', $message->getId(), $optParamsGet2);
//         $threadId = $single_message->getThreadId();
//         $payload  = $single_message->getPayload();
//         $headers  = $payload->getHeaders();
//         $sender   = $headers[16]['value'];
//         $receiver = $headers[20]['value'];
//         $parts    = $payload->getParts();
//         $body     = $parts[0]['body'];
//         $rawData  = $body->data;
//         $sanitizedData = strtr($rawData,'-_', '+/');
//         $decodedMessage = base64_decode($sanitizedData); 
  
//         $response['sender'] = $sender;
//         $response['receiver'] = $receiver;
//         $response['data'] = $decodedMessage;
//        // print  'Message with ID: ' . $decodedMessage . '<br/>';

//      // print 'Message with ID: ' . $message->getId() . ' retrieved.';

//       return $response;

//     } catch (Exception $e) {
//       print 'An error occurred: ' . $e->getMessage();
//     }
//   }


  

// if (count($results->getLabels()) == 0) {
//   print "No labels found.\n";
// } else {
//   print "Labels:\n";
//   foreach ($results->getLabels() as $label) {
//     printf("- %s\n", $label->getName());
//   }
// }