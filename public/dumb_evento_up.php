<?php

declare(strict_types=1);

namespace RechercheCreneaux;
$relativeRoot = '../';

$relativeRoot = '../';
require_once dirname(__DIR__).'/vendor/autoload.php';

session_start();

require_once dirname(__DIR__)."/src/includes/head_inc.php";

if ((isset($stdEnv->uidCasUser) && strlen($stdEnv->uidCasUser) > 1) == false) {
    http_response_code(404);
    // non authentifié exit
    exit;
}

$uidEvents = &$_SESSION['evento'][$stdEnv->uidCasUser];
$hashkey = null;

if (isset($_GET['path'])) {
    $path = $_GET['path'];
    // test si le sondage existe déja en session
    foreach ($uidEvents as $md5 => $event) {
        if ($event['path'] == $path) {
            $hashkey = $md5;
        }
    }
} else {
    $hashkey = md5(serialize($_GET));
}

$uidEvents[$hashkey] = $_GET;

http_response_code(200);
