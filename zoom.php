<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use RechercheCreneauxLib\ZoomUP1;

require 'vendor/autoload.php';

require_once("head.php");

$zoom = new ZoomUP1([
    'client_id' => $stdEnv->zoomClientId,
    'client_secret' => $stdEnv->zoomClientSecret,
    'redirect_uri' => 'https://creneaux-dev.univ-paris1.fr/zoom.php',
    'credential_path' => $stdEnv->zoomLibCredentialPath
  ]);

$zoom->tokenUP1($stdEnv->zoomAccountId);

$meetings = $zoom->listMeeting();

echo '<pre>';
die(var_export($meetings));

?>
