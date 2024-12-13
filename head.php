<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use stdClass;
use Exception;
use Dotenv\Dotenv;
use phpCAS;
use RechercheCreneaux\FBParams;

require 'vendor/autoload.php';

session_start();

// Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// valeures requises dans le fichier .env exception levée si ce n'est pas le cas
$dotenv->required(['ENV','APP_URL' ,'URL_FREEBUSY', 'TIMEZONE', 'LOCALE']);
$dotenv->required('RECHERCHE_SUR_X_JOURS')->isInteger();

setlocale(LC_TIME, $_ENV['LOCALE']);

$stdEnv = new stdClass();
$stdEnv->env = (isset($_ENV['ENV'])) ? $_ENV['ENV'] : 'dev';

$stdEnv->url = $_ENV['URL_FREEBUSY'];
$stdEnv->dtz = $_ENV['TIMEZONE'];
$stdEnv->rechercheSurXJours = intval($_ENV['RECHERCHE_SUR_X_JOURS']);

$dotenv->required(['WSGROUP', 'PHOTO_SHOW', 'PROLONGATION_BANDEAU', 'CAS', 'ZOOM', 'EVENTO'])->isBoolean();

$stdEnv->appUrl = $_ENV['APP_URL'];
$stdEnv->wsgroup = (boolean) json_decode(strtolower($_ENV['WSGROUP']));
$stdEnv->photoShow = (boolean) json_decode(strtolower($_ENV['PHOTO_SHOW']));
$stdEnv->prolongationBandeau = (boolean) json_decode(strtolower($_ENV['PROLONGATION_BANDEAU']));
$stdEnv->cas = (boolean) json_decode(strtolower($_ENV['CAS']));
$stdEnv->zoom = (boolean) json_decode(strtolower($_ENV['ZOOM']));
$stdEnv->evento = (boolean) json_decode(strtolower($_ENV['EVENTO']));
$stdEnv->kronolith = (boolean) json_decode(strtolower($_ENV['KRONOLITH']));

if ($stdEnv->wsgroup === true) {
    $dotenv->required(['URLWSGROUP_USERS_AND_GROUPS', 'URLWSGROUP_USER_INFOS']);
    $stdEnv->urlwsgroupUsersAndGroups = $_ENV['URLWSGROUP_USERS_AND_GROUPS'];
    $stdEnv->urlwsgroupUserInfos = $_ENV['URLWSGROUP_USER_INFOS'];
}

if ($stdEnv->photoShow === true) {
    $dotenv->required('URLWSPHOTO');
    $stdEnv->urlwsphoto = $_ENV['URLWSPHOTO'];
}

if ($stdEnv->prolongationBandeau === true) {
    $dotenv->required(['PROLONGATION_ENT_JS', 'PROLONGATION_ENT_ARGS_CURRENT']);

    $stdEnv->prolongationEntJs = $_ENV['PROLONGATION_ENT_JS'];
    $stdEnv->prolongationEntArgsCurrent = $_ENV['PROLONGATION_ENT_ARGS_CURRENT'];
}

if ($stdEnv->cas === true) {
    $dotenv->required(['CAS_HOST', 'CAS_PORT', 'CAS_PATH', 'APP_URL']);

    phpCAS::client(CAS_VERSION_2_0, $_ENV['CAS_HOST'], intval($_ENV['CAS_PORT']), $_ENV['CAS_PATH'], $_ENV['APP_URL']);
    phpCAS::setNoCasServerValidation();

    phpCAS::forceAuthentication();

    if (!phpCAS::isAuthenticated()) {
        throw new Exception("Recherche_de_creneaux CAS Error authentificated");
    }
    $stdEnv->uidCasUser = phpCAS::getUser();
}

if ($stdEnv->zoom === true) {
    $dotenv->required(['ZOOM_ACCOUNT_ID', 'ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_LIB_CREDENTIAL_PATH']);
    $stdEnv->zoomAccountId = $_ENV['ZOOM_ACCOUNT_ID'];
    $stdEnv->zoomClientId = $_ENV['ZOOM_CLIENT_ID'];
    $stdEnv->zoomClientSecret = $_ENV['ZOOM_CLIENT_SECRET'];
    $stdEnv->zoomLibCredentialPath = $_ENV['ZOOM_LIB_CREDENTIAL_PATH'];

    if (!file_exists($stdEnv->zoomLibCredentialPath))
        file_put_contents($stdEnv->zoomLibCredentialPath, '');
}

if ($stdEnv->evento == true) {
    $dotenv->required(['EVENTO_WS_URL', 'EVENTO_SHIBENTITYID']);
    $stdEnv->eventoWsUrl = $_ENV['EVENTO_WS_URL'];
    $stdEnv->eventoShibentityid = $_ENV['EVENTO_SHIBENTITYID'];
}

if ($stdEnv->kronolith == true) {
    $dotenv->required(['KRONOLITH_HOST', 'KRONOLITH_IMPORT_URL_USER']);
    $stdEnv->kronolith_host = $_ENV['KRONOLITH_HOST'];
    $stdEnv->kronolith_import_url_user = $_ENV['KRONOLITH_IMPORT_URL_USER'];
}


$stdEnv->mailfrom = $_ENV['MAILFROM'] ?? null;

if ($stdEnv->rjsfile = 'js/' . $_ENV['RJSFILE'] ?? null) {
    $dotenv->required(['RJSFILE'])->allowedRegexValues("/^.*\.(js)$/i");
}

date_default_timezone_set($stdEnv->dtz);

$stdEnv->varsHTTPGet = filter_var_array($_GET);

$fbParams = new FBParams($stdEnv);
