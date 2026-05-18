<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use Dotenv\Dotenv;
use phpCAS;
use stdClass;

/**
 * Classe regroupant les paramètres globaux
 * Issu d'une stdclass, a été formalisée comme classe pour plus de clarté
 */
class FBParams
{

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

    public function __construct(stdClass $stdEnv)
    {

        $this->stdEnv = $stdEnv;
        $this->actionFormulaireValider = isset($stdEnv->varsHTTPGet['actionFormulaireValider']) ? $stdEnv->varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
        $this->uids = isset($stdEnv->varsHTTPGet['listuids']) ? array_map(fn($uid) => ['type' => 'up1', 'uid' => $uid, 'data' => false, 'valid' => true], $stdEnv->varsHTTPGet['listuids']) : null; // array_map permet d'enlever les éléments vide de ce paramètre
        $this->nbcreneaux = isset($stdEnv->varsHTTPGet['creneaux']) ? (int) $stdEnv->varsHTTPGet['creneaux'] : null;
        $this->duree = isset($stdEnv->varsHTTPGet['duree']) ? (int) $stdEnv->varsHTTPGet['duree'] : null;
        $this->plagesHoraires = isset($stdEnv->varsHTTPGet['plagesHoraires']) ? $stdEnv->varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
        $this->joursDemandes = isset($stdEnv->varsHTTPGet['joursCreneaux']) ? $stdEnv->varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
        $this->fromDate = isset($stdEnv->varsHTTPGet['fromDate']) ? $stdEnv->varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
        $this->rechercheSurXJours = isset($stdEnv->varsHTTPGet['rechercheSurXJours']) ? intval($stdEnv->varsHTTPGet['rechercheSurXJours']) : $stdEnv->rechercheSurXJours;
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

        // si externalfbs est vrai dans la configuration .env , on continue l'execution du constructeur sinon on s'arrête
        if (! $stdEnv->agendasDistants) {
            return;
        }

        $agendasDistantsUrl = isset($stdEnv->varsHTTPGet['agendasDistantsUrl']) && is_array($stdEnv->varsHTTPGet['agendasDistantsUrl']) ? array_filter($stdEnv->varsHTTPGet['agendasDistantsUrl']) : null;
        $agendasDistantsMail = isset($stdEnv->varsHTTPGet['agendasDistantsMail']) && is_array($stdEnv->varsHTTPGet['agendasDistantsMail']) ? array_filter($stdEnv->varsHTTPGet['agendasDistantsMail']) : null;

        if ($agendasDistantsUrl && sizeof($agendasDistantsUrl) > 0) {
            foreach ($agendasDistantsUrl as $idx => $agendaDistantUrl) {
                $agendaDistantMail = $agendasDistantsMail[$idx];
                // test si des agendas externes sont en doublons (ne devrait pas arriver, controle js sur les entrées)
                if ($this->uids && array_filter($this->uids, fn($aUid) => array_key_exists('url', $aUid) && $aUid['url'] == $agendaDistantUrl))
                    throw new \Exception("Doublon sur les agenda extérieurs, contactez la DSIUN");

                $decodedUrl = urldecode($agendaDistantUrl);

                $aUid = ['url' => $agendaDistantUrl, 'uid' => $agendaDistantMail, 'data' => false, 'valid' => false];

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

    public static function initEnv(Dotenv $dotenv): stdClass
    {
        global $_GET;

        $dotenv->load();

        // valeures requises dans le fichier .env exception levée si ce n'est pas le cas
        $dotenv->required(['ENV', 'APP_URL', 'URL_FREEBUSY', 'TIMEZONE', 'LOCALE']);
        $dotenv->required('RECHERCHE_SUR_X_JOURS')->isInteger();

        setlocale(LC_TIME, $_ENV['LOCALE']);

        $stdEnv = new stdClass();
        $stdEnv->env = (isset($_ENV['ENV'])) ? $_ENV['ENV'] : 'dev';

        $stdEnv->url = $_ENV['URL_FREEBUSY'];
        $stdEnv->dtz = $_ENV['TIMEZONE'];
        $stdEnv->rechercheSurXJours = intval($_ENV['RECHERCHE_SUR_X_JOURS']);

        $dotenv->required(['WSGROUP', 'PHOTO_SHOW', 'PROLONGATION_BANDEAU', 'CAS', 'ZOOM', 'EVENTO', 'AGENDASDISTANTS'])->isBoolean();

        $stdEnv->appUrl = $_ENV['APP_URL'];

        isset($_ENV['WSGROUP']) ? $stdEnv->wsgroup = (bool) json_decode(strtolower($_ENV['WSGROUP'])) : $stdEnv->wsgroup=false;
        isset($_ENV['PHOTO_SHOW']) ? $stdEnv->photoShow = (bool) json_decode(strtolower($_ENV['PHOTO_SHOW'])) : $stdEnv->photoShow=false;
        isset($_ENV['PROLONGATION_BANDEAU']) ? $stdEnv->prolongationBandeau = (bool) json_decode(strtolower($_ENV['PROLONGATION_BANDEAU'])) : $stdEnv->prolongationBandeau=false;
        isset($_ENV['CAS']) ? $stdEnv->cas = (bool) json_decode(strtolower($_ENV['CAS'])) : $stdEnv->cas=false;
        isset($_ENV['ZOOM']) ? $stdEnv->zoom = (bool) json_decode(strtolower($_ENV['ZOOM'])) : $stdEnv->zoom=false;
        isset($_ENV['EVENTO']) ? $stdEnv->evento = (bool) json_decode(strtolower($_ENV['EVENTO'])) : $stdEnv->evento=false;
        isset($_ENV['KRONOLITH']) ? $stdEnv->kronolith = (bool) json_decode(strtolower($_ENV['KRONOLITH'])) : $stdEnv->kronolith=false;
        isset($_ENV['AGENDASDISTANTS']) ? $stdEnv->agendasDistants = (bool) json_decode(strtolower($_ENV['AGENDASDISTANTS'])) : $stdEnv->agendasDistants=false;
        isset($_ENV['AGENDASDISTANTSUPLOAD']) && $stdEnv->agendasDistants ? $stdEnv->agendasDistantsUpload = (bool) json_decode(strtolower($_ENV['AGENDASDISTANTSUPLOAD'])) : $stdEnv->agendasDistantsUpload = false;

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
                header('HTTP/1.1 401 Unauthorized');
                echo "Recherche_de_creneaux CAS Error authentificated";
                exit;
            }
            $stdEnv->uidCasUser = phpCAS::getUser();
        }

        if ($stdEnv->zoom === true) {
            $dotenv->required(['ZOOM_ACCOUNT_ID', 'ZOOM_CLIENT_ID', 'ZOOM_CLIENT_SECRET', 'ZOOM_LIB_CREDENTIAL_PATH']);
            $stdEnv->zoomAccountId = $_ENV['ZOOM_ACCOUNT_ID'];
            $stdEnv->zoomClientId = $_ENV['ZOOM_CLIENT_ID'];
            $stdEnv->zoomClientSecret = $_ENV['ZOOM_CLIENT_SECRET'];
            $stdEnv->zoomLibCredentialPath = $_ENV['ZOOM_LIB_CREDENTIAL_PATH'];

            if (!file_exists($stdEnv->zoomLibCredentialPath)) {
                file_put_contents($stdEnv->zoomLibCredentialPath, '');
            }
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

        date_default_timezone_set($stdEnv->dtz);

        $stdEnv->varsHTTPGet = filter_var_array($_GET);

        return $stdEnv;
    }
}
