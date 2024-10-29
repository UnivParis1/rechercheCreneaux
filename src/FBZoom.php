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
use Exception;

class FBZoom
{
    private FBParams $fbParams;
    private stdClass $stdEnv;
    private stdClass $userMailStd;
    private ZoomUP1 $zoom;
    private array $users;
    private string $titleEvent;
    private string $descriptionEvent;
    private string $modalCreneauStart;
    private string $modalCreneauEnd;
    private int $duree;
    private string $sessionName;

    public function __construct(FBParams $fbParams, stdClass $stdEnv) {
        $titleEvent = $fbParams->titleEvent;
        $descriptionEvent = $fbParams->descriptionEvent;
        $duree = $fbParams->duree;
        $modalCreneauStart = $fbParams->modalCreneauStart;
        $modalCreneauEnd = $fbParams->modalCreneauEnd;
        $sessionName = $fbParams->zoomSessionName;

        if ($fbParams->actionFormulaireValider !== 'zoomMeeting') {
            throw new Exception("action != zoomMeeting");
        }

        if (! ($titleEvent && $descriptionEvent && $modalCreneauStart && $modalCreneauEnd)) {
            throw new Exception('null modalCreneauStart && modalCreneauEnd');
        }

        $userMailStd = FBUser::_getUidInfos($fbParams->stdEnv->uidCasUser, $fbParams->stdEnv);

        $users = [];
        foreach ($fbParams->uids as $uid) {
            $infos = FBUser::_getUidInfos($uid, $fbParams->stdEnv);

            if ($infos->mail != $userMailStd->mail) {
                $users[] = $infos;
            }
        }

        $zoom = new ZoomUP1([
            'client_id' => $stdEnv->zoomClientId,
            'client_secret' => $stdEnv->zoomClientSecret,
            'redirect_uri' => $stdEnv->appUrl . '/zoom.php',
            'credential_path' => $stdEnv->zoomLibCredentialPath
          ]);

        $zoom->tokenUP1($stdEnv->zoomAccountId);

        $this->fbParams = $fbParams;
        $this->titleEvent = $titleEvent;
        $this->descriptionEvent = $descriptionEvent;
        $this->duree = $duree;
        $this->sessionName = $sessionName;
        $this->modalCreneauStart = $modalCreneauStart;
        $this->modalCreneauEnd = $modalCreneauEnd;
        $this->userMailStd = $userMailStd;
        $this->users = $users;
        $this->stdEnv = $stdEnv;
        $this->zoom = $zoom;
    }

    public function createZoomMeeting() {
        $sessionName = $this->sessionName;

        if (!isset($_SESSION[$sessionName]))
            $_SESSION[$sessionName] = [];

        $zoom = $this->zoom;
        $userMailStd = $this->userMailStd;
        $stdEnv = $this->stdEnv;

        $idxSessionDate = FBUtils::getIdxCreneauxWithStartEnd($_SESSION[$sessionName], new DateTime($this->modalCreneauStart), new DateTime($this->modalCreneauEnd));
        $idxSessionDate = ($idxSessionDate !== -1) ? $idxSessionDate: count($_SESSION[$sessionName]);

        if (isset($_SESSION[$sessionName][$idxSessionDate])) {
            if (! ($data = $_SESSION[$sessionName][$idxSessionDate]['data'])) {
                throw new Exception("datas hors session");
            }
            return $data;
        }

        if (! self::verifZoomMeeting($this->modalCreneauStart, $this->modalCreneauEnd, $zoom, $userMailStd->mail, $stdEnv)) {
            throw new Exception('Meeting existant');
        }

        $meetingData = ['topic' => $this->titleEvent,
                        'type' => 2, // 1 pour une réunion instantanée, 2 pour une réunion planifiée
                        'start_time' => (new DateTimeImmutable($this->modalCreneauStart))->setTimeZone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sp'), // Format ISO 8601 pour l'heure de début
                        'duration' => $this->duree, // Durée de la réunion en minutes
                        'timezone' => 'Europe/Paris', // Fuseau horaire de la réunion
                        'settings' => ['meeting_invitees' => []]
                        ];

        foreach ($this->users as $invite) {
            $meetingData['settings']['meeting_invitees'][] = ['email' => $invite->mail];
        }

        $data = $zoom->createMeeting($userMailStd->mail, $meetingData);

        if (!isset($_SESSION[$sessionName][$idxSessionDate])) {
            $_SESSION[$sessionName][$idxSessionDate] = [];
            $_SESSION[$sessionName][$idxSessionDate]['modalCreneau'] = ['modalCreneauStart' => $this->modalCreneauStart, 'modalCreneauEnd' => $this->modalCreneauEnd];
            $_SESSION[$sessionName][$idxSessionDate]['infos'] = ['titleEvent' => $this->titleEvent, 'descriptionEvent' => $this->descriptionEvent, 'userHost' => $this->userMailStd, 'userParticipants' => $this->users];

            // retour js cas 1er appel, impossible pour le front de récupérer la session php
            // , valeur retourné par le js (avant d'affecter les datas à la session pour éviter la redondance)
            // d'où l'affectation de la variable ici
            $data[$sessionName] = $_SESSION[$sessionName];
            $_SESSION[$sessionName][$idxSessionDate]['data'] = $data['data'];
        }
        return $data;
    }

    public function listMeetings($mail) {
        return $this->zoom->listMeeting($mail, ['type' => 'scheduled']);
    }

    public function createAttendees($datas) {
        $users = $this->users;
        $zoom = $this->zoom;

        $attendees = ['attendees' => [] ];

        foreach ($users as $user)
            $attendees['attendees'][] = ['name' => $user->displayName];

        $dataInviteLink = $zoom->inviteLink($datas['data']['id'], $attendees);

        return $dataInviteLink;
    }

    protected static function verifZoomMeeting(string $dstart, string $dend, ZoomUP1 $zoom, string $mail, stdClass $stdEnv) {
        $dPeriod = Period::fromDate(new DateTime($dstart), new DateTime($dend));
      
        $meetings = $zoom->listMeeting($mail, array('type' => 'scheduled'));
      
        if (sizeof($meetings['data']['meetings']) == 0)
          return true;
      
        foreach ($meetings['data']['meetings'] as $meet) {
          if (!array_key_exists('start_time', $meet))
            continue;

          $mstartDate = (new DateTimeImmutable($meet['start_time']))->setTimezone(new DateTimeZone($stdEnv->dtz));
          $mPeriod = Period::fromDate($mstartDate, $mstartDate->add(new DateInterval('PT'. $meet['duration'] .'M')));
      
          if ($dPeriod->overlaps($mPeriod))
            return false;
        }
      
        return true;
      }

}