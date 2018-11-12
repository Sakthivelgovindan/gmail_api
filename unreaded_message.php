
<?php
require './vendor/autoload.php';

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
$client  = getClient();
$service = new Google_Service_Gmail($client);

// Print the labels in the user's account.
$userId = 'me';

$result = listMessages($service, $userId);

print_r($result);

/**
 * Return unread message list
 * @param object $service
 * @param string $userId
 * 
 * @return array $response
 */

function listMessages($service, $userId)
{
    $pageToken = NULL;
    $messages  = array();
    $opt_param = array();
    $q         = 'from:Sakthivel Govindan <sakthivelgovindan@designqubearchitects.com> is:unread';
    $response  = array();
    do {
        try {
            if ($pageToken) {
                $opt_param['pageToken'] = $pageToken;
            }
            
            $opt_param['q']   = $q;
            $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
            if ($messagesResponse->getMessages()) {
                $messages  = array_merge($messages, $messagesResponse->getMessages());
                $pageToken = $messagesResponse->getNextPageToken();
            }
        }
        catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }
    } while ($pageToken);
    
    foreach ($messages as $message) {
        
        $value = getMessage($service, $userId, $message['id']);
        
        array_push($response, $value);
    }
    
    
    return $response;
}


/**
 * Return email content
 * @param object $service 
 * @param string $userId
 * @param string $messageId
 * @param array $response
 * 
 * @return array $response
 */

function getMessage($service, $userId, $messageId)
{
  
    $response = array();
    try {
    
        $message = $service->users_messages->get($userId, $messageId);    
        $optParamsGet2['format'] = 'full';
        $single_message          = $service->users_messages->get('me', $message->getId(), $optParamsGet2);
        $threadId                = $single_message->getThreadId();
        $payload                 = $single_message->getPayload();
        $headers                 = $payload->getHeaders();
        
        foreach ($headers as $single) {
            
            if ($single->getName() == 'To') {
                
                $receiver = $single->getValue();
                
            }
            
            else if ($single->getName() == 'From') {
                
                $sender = $single->getValue();
                
            }
        }

        $parts          = $payload->getParts();
        $body           = $parts[0]['body'];
        $rawData        = $body->data;
        $sanitizedData  = strtr($rawData, '-_', '+/');
        $decodedMessage = base64_decode($sanitizedData);
        $response['sender']   = $sender;
        $response['receiver'] = $receiver;
        $response['data']     = $decodedMessage;

        return $response;
    }
    catch (Exception $e) {
        print 'An error occurred: ' . $e->getMessage();
    }
}
