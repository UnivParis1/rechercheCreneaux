<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use Exception;
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
        if ($stdEnv->agendasDistants) {
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

        if ($stdEnv->kronolithTagCals == true) {
            $agdDmds = $stdEnv->varsHTTPGet['agdRsrc'] ?? [];

            $url = "{$stdEnv->kronolithTagCalsUrl}" . "{$stdEnv->uidCasUser}";
            try {
                $contextStream = $stdEnv->env == 'local' ? stream_context_create(['ssl' => [ 'verify_peer' => false, 'verify_peer_name' => false]]) : null;
                $response = file_get_contents($url, false, $contextStream);
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
    }
}
