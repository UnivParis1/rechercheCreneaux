<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use stdClass;
use Dotenv\Dotenv;
use Exception;
use RechercheCreneaux\FBParams;

global $relativeRoot;

function convCamelCase(string $string): string {
    return lcfirst(str_replace('_', '', ucwords($string, '_')));
}
// Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
$dotenv = Dotenv::createImmutable($relativeRoot);
$dotenv->load();

// valeures requises dans le fichier .env exception levée si ce n'est pas le cas
$dotenv->required(['ENV', 'APP_URL', 'URL_FREEBUSY', 'TIMEZONE', 'LOCALE', 'MAILFROM']);

$stdEnv = new stdClass();

$parametresBooleens = ['ENV', 'APP_URL','URL_FREEBUSY','TIMEZONE', 'LOCALE','RECHERCHE_SUR_X_JOURS', 'MAILFROM'];

foreach($parametresBooleens as $v) {
    if (! array_key_exists($v, $_ENV))
        throw new Exception("{$v} absent du fichier environnement");

    $camelcase = convCamelCase(strtolower($v));
    $stdEnv->{$camelcase} = $_ENV[$v];
}

$afonctions = ['WSGROUP' => ['URLWSGROUP_USERS_AND_GROUPS', 'URLWSGROUP_USER_INFOS'],
       'PHOTO_SHOW' => ['URLWSPHOTO'],
       'PROLONGATION_BANDEAU' => ['PROLONGATION_ENT_JS', 'PROLONGATION_ENT_ARGS_CURRENT'],
       'CAS' => ['CAS_HOST', 'CAS_PORT', 'CAS_PATH', 'APP_URL'],
       'ZOOM'=> ['ZOOM_ACCOUNT_ID', 'ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_LIB_CREDENTIAL_PATH'],
       'EVENTO' => ['EVENTO_WS_URL', 'EVENTO_SHIBENTITYID'],
       'KRONOLITH' => ['KRONOLITH_HOST', 'KRONOLITH_IMPORT_URL_USER'],
       'AGENDAS_DISTANTS' => [] ];

foreach ($afonctions as $fonction => $subv) {
    if (! array_key_exists($fonction, $_ENV))
        throw new Exception("{$fonction} absent du fichier environnement");

    $fonctionc = convCamelCase(strtolower($fonction));

    $isTrue = (bool) json_decode(strtolower($_ENV[$fonction]));
    $stdEnv->{$fonctionc} = $isTrue;

    if ($isTrue) {
        foreach($subv as $sub) {
            $subc = convCamelCase(strtolower($sub));
            $stdEnv->{$subc} = $_ENV[$sub];
        }
    }
}
$fbParams = FBParams::factory($stdEnv);
