<?php
declare(strict_types=1);

namespace RechercheCreneaux;

use RechercheCreneauxLib\ZoomUP1;
use League\Period\Period;
use stdClass;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

class FBZoom
{
    var $fbParams;
    var $stdEnv;
    var $userMailStd;
    var $users;
    var $zoom;
    var $titleEvent;
    var $descriptionEvent;
    var $modalCreneauStart;
    var $modalCreneauEnd;
    var $duree;

    public function __construct(FBParams $fbParams, $stdEnv) {

        $titleEvent = $fbParams->titleEvent;
        $descriptionEvent = $fbParams->descriptionEvent;
        $duree = $fbParams->duree;
        $modalCreneauStart = $fbParams->modalCreneauStart;
        $modalCreneauEnd = $fbParams->modalCreneauEnd;

        if ($fbParams->actionFormulaireValider !== 'zoomMeeting') {
            throw new \Exception("action != zoomMeeting");
        }

        if (! ($titleEvent && $descriptionEvent && $modalCreneauStart && $modalCreneauEnd)) {
            throw new \Exception('null modalCreneauStart && modalCreneauEnd');
        }

        $userMailStd = FBUser::_getUidInfos($fbParams->stdEnv->uidCasUser, $fbParams->stdEnv);

        $users = [];
        foreach ($fbParams->uids as $uid) {
        $infos = FBUser::_getUidInfos($uid, $fbParams->stdEnv);
        if ($infos->mail != $userMailStd->mail)
            $users[] = $infos;
        }

        $zoom = new ZoomUP1([
            'client_id' => $stdEnv->zoomClientId,
            'client_secret' => $stdEnv->zoomClientSecret,
            'redirect_uri' => 'https://creneaux-dev.univ-paris1.fr/zoom.php',
            'credential_path' => $stdEnv->zoomLibCredentialPath
          ]);

        $zoom->tokenUP1($stdEnv->zoomAccountId);

        $this->fbParams = $fbParams;
        $this->titleEvent = $titleEvent;
        $this->descriptionEvent = $descriptionEvent;
        $this->duree = $duree;
        $this->modalCreneauStart = $modalCreneauStart;
        $this->modalCreneauEnd = $modalCreneauEnd;
        $this->userMailStd = $userMailStd;
        $this->users = $users;
        $this->stdEnv = $stdEnv;
        $this->zoom = $zoom;
    }

    public function createZoomMeeting() {
        if (!isset($_SESSION['zoomMeeting']))
            $_SESSION['zoomMeeting'] = array();

        $zoom = $this->zoom;
        $userMailStd = $this->userMailStd;
        $stdEnv = $this->stdEnv;

        if (! $this->verifZoomMeeting($this->modalCreneauStart, $this->modalCreneauEnd, $zoom, $userMailStd->mail, $stdEnv)) {
            throw new \Exception('Meeting existant');
        }

        $meetingData = array(
        'topic' => $this->titleEvent,
        'type' => 2, // 1 pour une réunion instantanée, 2 pour une réunion planifiée
        'start_time' => (new DateTimeImmutable($this->modalCreneauStart))->setTimeZone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sp'), // Format ISO 8601 pour l'heure de début
        'duration' => $this->duree, // Durée de la réunion en minutes
        'timezone' => 'Europe/Paris' // Fuseau horaire de la réunion
        );
        
        $datas = $zoom->createMeeting($userMailStd->mail, $meetingData);

        $idxSessionDate = FBUtils::getIdxCreneauxWithStartEnd($_SESSION['zoomMeeting'], new DateTime($this->modalCreneauStart), new DateTime($this->modalCreneauEnd));
        $idxSessionDate = ($idxSessionDate !== -1) ? $idxSessionDate: count($_SESSION['zoomMeeting']);

        return $datas;
    }

    public function listMeetings($mail) {
        return $this->zoom->listMeeting($mail, array('type' => 'scheduled'));
    }

    public function createAttendees($datas) {
        $users = $this->users;
        $zoom = $this->zoom;

        $attendees = ['attendees' => array()];

        foreach ($users as $user)
        $attendees['attendees'][] = ['name' => $user->displayName];

        $dataInviteLink = $zoom->inviteLink($datas['data']['id'], $attendees);

        return $dataInviteLink;
    }

    private function verifZoomMeeting(string $dstart, string $dend, ZoomUP1 $zoom, string $mail, stdClass $stdEnv) {
        $dPeriod = Period::fromDate(new DateTime($dstart), new DateTime($dend));
      
        $meetings = $zoom->listMeeting($mail, array('type' => 'scheduled'));
      
        if (sizeof($meetings['data']['meetings']) == 0)
          return true;
      
        foreach ($meetings['data']['meetings'] as $meet) {
      
          $mstartDate = (new DateTimeImmutable($meet['start_time']))->setTimezone(new DateTimeZone($stdEnv->dtz));
          $mPeriod = Period::fromDate($mstartDate, $mstartDate->add(new DateInterval('PT'. $meet['duration'] .'M')));
      
          if ($dPeriod->overlaps($mPeriod))
            return false;
        }
      
        return true;
      }

}