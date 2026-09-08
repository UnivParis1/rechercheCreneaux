<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use Exception;
use stdClass;
use phpCAS;
use Dotenv\Dotenv;

/**
 * Classe regroupant les paramètres globaux
 * Issu d'une stdclass, a été formalisée comme classe pour plus de clarté
 */
class FBParams
{
    /*
    * array $parmaRequis
    * paramètres devant se trouver dans le fichier .env
    */
    private static array $paramsRequis = ['ENV', 'APP_URL','URL_FREEBUSY','TIMEZONE', 'LOCALE','RECHERCHE_SUR_X_JOURS', 'MAILFROM'];

    /*
    * array $diagrammeParams
    * définit le tableau des paramètres venant de .env
    * ex: si PHOTO_SHOW est true, URLWSPHOTO devra être présent dans .env
    */
    private static array $diagrammeParams = ['WSGROUP' => ['URLWSGROUP_USERS_AND_GROUPS', 'URLWSGROUP_USER_INFOS'],
       'PHOTO_SHOW' => ['URLWSPHOTO'],
       'PROLONGATION_BANDEAU' => ['PROLONGATION_ENT_JS', 'PROLONGATION_ENT_ARGS_CURRENT'],
       'CAS' => ['CAS_HOST', 'CAS_PORT', 'CAS_PATH', 'APP_URL'],
       'ZOOM'=> ['ZOOM_ACCOUNT_ID', 'ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_LIB_CREDENTIAL_PATH'],
       'EVENTO' => ['EVENTO_WS_URL', 'EVENTO_SHIBENTITYID'],
       'KRONOLITH' => ['KRONOLITH_HOST', 'KRONOLITH_IMPORT_URL_USER'],
       'KRONOLITH_TAG_CALS' => ['KRONOLITH_TAG_CALS_URL', 'KRONOLITH_URL_FREEBUSY'],
       'AGENDAS_DISTANTS' => [] ];

    var array $varsHTTPGet;

    var $actionFormulaireValider;

    var ?array $uids;

    var ?int $nbcreneaux;

    var ?int $duree;

    var array $plagesHoraires;

    var array $joursDemandes;

    var int $rechercheSurXJours;

    var string $fromDate;

    var ?array $idxCreneauxChecked;
    var ?string $titleEvent;

    var ?string $descriptionEvent;

    var ?string $lieuEvent;

    var ?string $modalCreneauStart;

    var ?string $modalCreneauEnd;
    var ?string $titreEvento;
    var ?string $summaryevento;

    var ?array $listUidsOptionnels;

    var ?string $jsonSessionInviteInfos;
    var ?string $jsonSessionZoomInfos;

    public stdClass $stdEnv;

    public string $inviteEnregistrementSessionName = 'inviteEnregistrement';

    public string $zoomSessionName = 'zoomMeeting';

    private function __construct(stdClass $stdEnv)
    {
        $this->stdEnv = $stdEnv;
        $this->actionFormulaireValider = isset($stdEnv->varsHTTPGet['actionFormulaireValider']) ? $stdEnv->varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
        $this->uids = isset($stdEnv->varsHTTPGet['listuids']) ? array_map(fn($uid) => ['type' => 'up1', 'uid' => $uid, 'data' => false, 'valid' => true], $stdEnv->varsHTTPGet['listuids']) : null; // array_map permet d'enlever les éléments vide de ce paramètre
        $this->nbcreneaux = isset($stdEnv->varsHTTPGet['creneaux']) ? (int) $stdEnv->varsHTTPGet['creneaux'] : null;
        $this->duree = isset($stdEnv->varsHTTPGet['duree']) ? (int) $stdEnv->varsHTTPGet['duree'] : null;
        $this->plagesHoraires = isset($stdEnv->varsHTTPGet['plagesHoraires']) ? $stdEnv->varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
        $this->joursDemandes = isset($stdEnv->varsHTTPGet['joursCreneaux']) ? $stdEnv->varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
        $this->fromDate = isset($stdEnv->varsHTTPGet['fromDate']) ? $stdEnv->varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
        $this->rechercheSurXJours = isset($stdEnv->varsHTTPGet['rechercheSurXJours']) ? intval($stdEnv->varsHTTPGet['rechercheSurXJours']) : intval($stdEnv->rechercheSurXJours);
        $this->idxCreneauxChecked = isset($stdEnv->varsHTTPGet['idxCreneauxChecked']) ? $stdEnv->varsHTTPGet['idxCreneauxChecked'] : null;
        $this->titleEvent = isset($stdEnv->varsHTTPGet['titrecreneau']) ? $stdEnv->varsHTTPGet['titrecreneau'] : null;
        $this->descriptionEvent = isset($stdEnv->varsHTTPGet['summarycreneau']) ? $stdEnv->varsHTTPGet['summarycreneau'] : null;
        $this->lieuEvent = isset($stdEnv->varsHTTPGet['lieucreneau']) ? $stdEnv->varsHTTPGet['lieucreneau'] : null;
        $this->modalCreneauStart = isset($stdEnv->varsHTTPGet['modalCreneauStart']) ? $stdEnv->varsHTTPGet['modalCreneauStart'] : null;
        $this->modalCreneauEnd = isset($stdEnv->varsHTTPGet['modalCreneauEnd']) ? $stdEnv->varsHTTPGet['modalCreneauEnd'] : null;
        $this->listUidsOptionnels = isset($stdEnv->varsHTTPGet['listUidsOptionnels']) ? $stdEnv->varsHTTPGet['listUidsOptionnels'] : null;
        $this->titreEvento = isset($stdEnv->varsHTTPGet['titrevento']) ? $stdEnv->varsHTTPGet['titrevento'] : null;
        $this->summaryevento = isset($stdEnv->varsHTTPGet['summaryevento']) ? $stdEnv->varsHTTPGet['summaryevento'] : null;
        $this->jsonSessionInviteInfos = isset($_SESSION[$this->inviteEnregistrementSessionName]) ? json_encode($_SESSION[$this->inviteEnregistrementSessionName]) : null;
        $this->jsonSessionZoomInfos = isset($_SESSION[$this->zoomSessionName]) ? json_encode($_SESSION[$this->zoomSessionName]) : null;

        if ((new DateTime($this->fromDate)) < (new DateTime())) {
            $this->fromDate = (new DateTime())->format('Y-m-d');
        }
    }


    private function initAgendasDistants()
    {
        $stdEnv = $this->stdEnv;
        // si externalfbs est vrai dans la configuration .env , on continue l'execution du constructeur sinon on s'arrête
        $agendasDistantsUrl = isset($stdEnv->varsHTTPGet['agendasDistantsUrl']) && is_array($stdEnv->varsHTTPGet['agendasDistantsUrl']) ? array_filter($stdEnv->varsHTTPGet['agendasDistantsUrl']) : null;
        $agendasDistantsMail = isset($stdEnv->varsHTTPGet['agendasDistantsMail']) && is_array($stdEnv->varsHTTPGet['agendasDistantsMail']) ? array_filter($stdEnv->varsHTTPGet['agendasDistantsMail']) : null;

        if ($agendasDistantsUrl && sizeof($agendasDistantsUrl) > 0) {
            foreach ($agendasDistantsUrl as $idx => $agendaDistantUrl) {
                $agendaDistantMail = $agendasDistantsMail[$idx];
                // test si des agendas externes sont en doublons (ne devrait pas arriver, controle js sur les entrées)
                if ($this->uids && array_filter($this->uids, fn($aUid) => array_key_exists('url', $aUid) && $aUid['url'] == $agendaDistantUrl))
                    throw new \Exception("Doublon sur les agenda extérieurs, contactez la DSIUN");

                $decodedUrl = urldecode($agendaDistantUrl);

                $aUid = ['url' => $agendaDistantUrl, 'uid' => $agendaDistantMail, 'code' => -1, 'valid' => false];

                $emailPattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

                if (str_starts_with($decodedUrl, "https://calendar.google.com") && preg_match($emailPattern, $decodedUrl, $matches)) {
                    $aUid['type'] = 'gmail';
                } else {
                    $aUid['type'] = 'default';
                }

                $this->uids[] = $aUid;
            }
        }
    }

    private static function setCookieHorde(string $url, string $cookiefile) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
        curl_exec($ch);
        curl_close($ch);

        return $cookiefile;
    }

    private function initAgendaTagRessources()
    {
        $stdEnv = $this->stdEnv;
        $agdDmds = $stdEnv->varsHTTPGet['agdRsrc'] ?? [];

        $url = "{$stdEnv->kronolithTagCalsUrl}" . "{$stdEnv->uidCasUser}";

        $cookiefile = "/tmp/sessionhordecreneau.txt";
        $cookie = self::setCookieHorde($url, $cookiefile);
        try {
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
            $response = curl_exec($ch);
            $agdRsrcs = json_decode($response);
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Veuillez contacter la DSIUN, {$e->getMessage()}");
        }

        if (false === is_array($agdRsrcs)) {
            $erreurMsg = "Erreur agendas ressources horde, si vous voyez ce message, veuillez contacter la DSIUN";
            error_log($erreurMsg);
            throw new Exception($erreurMsg);
        }


        foreach($agdRsrcs as $agdRsrc) {
            $cal = $agdRsrc->calendar;

            $test = false;
            foreach($agdDmds as $agdDmd) {
                if ($agdDmd == $cal) {
                    $test = true;
                    break;
                }
            }

            $this->uids[] = ['type' => 'up1cal', 'uid' => $agdRsrc->calendar, 'name' => $agdRsrc->name, 'checked' => $test ? true : false];
        }
    }

    private function logToCas() {
        phpCAS::client('2.0', $this->stdEnv->casHost, intval($this->stdEnv->casPort), $this->stdEnv->casPath, $this->stdEnv->appUrl);
        phpCAS::setNoCasServerValidation();

        phpCAS::forceAuthentication();

        if (!phpCAS::isAuthenticated()) {
            header('HTTP/1.1 401 Unauthorized');
            echo "Recherche_de_creneaux CAS Error authentificated";
            exit;
        }
        $this->stdEnv->uidCasUser = phpCAS::getUser();
    }

    private static function convCamelCase(string $string): string {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    private static function initStdEnv(): stdClass
    {
        global $relativeRoot;
        global $_GET;

        // Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
        $dotenv = Dotenv::createImmutable($relativeRoot);
        $dotenv->load();

        $envars = $_ENV;
        // valeures requises dans le fichier .env exception levée si ce n'est pas le cas
        $dotenv->required(self::$paramsRequis);

        $stdEnv = new stdClass();

        $fctEnAvance = false;
        if (isset($envars['AVANCE_VERSION']) && $envars['AVANCE_VERSION'] == "true") {
            $stdEnv->hiddenGetFields = [];
            $fctEnAvance = true;
        }

        foreach(self::$paramsRequis as $v) {
            if (! array_key_exists($v, $envars))
                throw new Exception("{$v} absent du fichier environnement");

            $camelcase = self::convCamelCase(strtolower($v));
            $stdEnv->{$camelcase} = $envars[$v];
        }

        foreach (self::$diagrammeParams as $fonctionnalité => $subv) {
            if (! array_key_exists($fonctionnalité, $envars))
                throw new Exception("{$fonctionnalité} absent du fichier environnement");

            $fonctionnalitéc = self::convCamelCase(strtolower($fonctionnalité));

            $isTrue = (bool) json_decode(strtolower($envars[$fonctionnalité]));

            // preview d'une fonctionnaliténalité
            if (!$isTrue && $fctEnAvance && (isset($_GET[$fonctionnalité]) && $_GET[$fonctionnalité] == "true") ) {
                $stdEnv->hiddenGetFields[] = $fonctionnalité;
                $isTrue =  true;
            }
            $stdEnv->{$fonctionnalitéc} = $isTrue;

            if ($isTrue) {
                foreach($subv as $sub) {
                    $subc = self::convCamelCase(strtolower($sub));
                    $stdEnv->{$subc} = $envars[$sub];
                }
            }
        }
        return $stdEnv;
    }

    public static function factory(): self {
        global $_GET;
        $getvars = $_GET;

        $stdEnv = self::initStdEnv();
        $stdEnv->varsHTTPGet = filter_var_array($getvars);

        date_default_timezone_set($stdEnv->timezone);
        setlocale(LC_TIME, $stdEnv->locale);

        $fbParam = new self($stdEnv);

        if ($stdEnv->cas == true)
            $fbParam->logToCas();

        if ($stdEnv->kronolithTagCals == true)
            $fbParam->initAgendaTagRessources();

        if ($stdEnv->agendasDistants == true)
            $fbParam->initAgendasDistants();

        if ($stdEnv->zoom)
            if (!file_exists($stdEnv->zoomLibCredentialPath))
                file_put_contents($stdEnv->zoomLibCredentialPath, '');

        return $fbParam;
    }
}
