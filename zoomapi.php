<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use RechercheCreneauxLib\ZoomUP1;
use Swagger;
use Swagger\Client\Configuration;
use Swagger\Client\Api\MeetingsApi;
use Exception;

require 'vendor/autoload.php';

require_once("head.php");

$zoom = new ZoomUP1([
    'client_id' => $stdEnv->zoomClientId,
    'client_secret' => $stdEnv->zoomClientSecret,
    'redirect_uri' => 'https://creneaux-dev.univ-paris1.fr/zoom.php',
    'credential_path' => $stdEnv->zoomLibCredentialPath
  ]);

$zoom->tokenUP1($stdEnv->zoomAccountId);

$meetings = $zoom->listMeeting('etienne.bohm@univ-paris1.fr');

require 'vendor/bavamca/zoom-integration-php/vendor/autoload.php';
$config = Swagger\Client\Configuration::getDefaultConfiguration()->setApiKey('access_token', $zoom->getCredentialData()['access_token']);
//$config = Swagger\Client\Configuration::getDefaultConfiguration()->setAccessToken(((object) $zoom->getCredentialData())->access_token);
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
$config = Configuration::getDefaultConfiguration()->setApiKeyPrefix('Authorization', 'Bearer');

$apiInstance = new MeetingsApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    $zoom->getGuzzleHttpClient(),
    $config
);

try {
    $result = $apiInstance->meetings('me', null, null, null);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling AccountsApi->meetings: ', $e->getMessage(), PHP_EOL;
}

?>
