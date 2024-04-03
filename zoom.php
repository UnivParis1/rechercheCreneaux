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

require 'vendor/autoload.php';

require_once("head.php");

function verifZoomMeeting(string $dstart, string $dend, ZoomUP1 $zoom, string $mail, stdClass $stdEnv) {
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


$zoom = new ZoomUP1([
    'client_id' => $stdEnv->zoomClientId,
    'client_secret' => $stdEnv->zoomClientSecret,
    'redirect_uri' => 'https://creneaux-dev.univ-paris1.fr/zoom.php',
    'credential_path' => $stdEnv->zoomLibCredentialPath
  ]);

$zoom->tokenUP1($stdEnv->zoomAccountId);

$meetings = $zoom->listMeeting('Etienne.Bohm@univ-paris1.fr', array('type' => 'scheduled'));

if ($fbParams->actionFormulaireValider !== 'zoomMeeting') {
  echo json_encode(['status' => false, 'msg' => 'action != zoomMeeting']);
  exit;
}

if (! ($fbParams->titleEvent && $fbParams->modalCreneauStart && $fbParams->modalCreneauEnd)) {
  echo json_encode(['status' => false, 'msg' => 'null modalCreneauStart && modalCreneauEnd']);
  exit;
}

$userMailStd = FBUser::_getUidInfos($fbParams->stdEnv->uidCasUser, $fbParams->stdEnv);

$users = [];
foreach ($fbParams->uids as $uid) {
  $infos = FBUser::_getUidInfos($uid, $fbParams->stdEnv);
  if ($infos->mail != $userMailStd->mail)
    $users[] = $infos;
}

if (! verifZoomMeeting($fbParams->modalCreneauStart, $fbParams->modalCreneauEnd, $zoom, $userMailStd->mail, $stdEnv)) {
  echo json_encode(['status' => false, 'msg' => 'Meeting existant']);
  exit;
}

$meetingData = array(
  'topic' => $fbParams->titleEvent,
  'type' => 2, // 1 pour une réunion instantanée, 2 pour une réunion planifiée
  'start_time' => (new DateTimeImmutable($fbParams->modalCreneauStart))->setTimeZone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sp'), // Format ISO 8601 pour l'heure de début
  'duration' => $fbParams->duree, // Durée de la réunion en minutes
  'timezone' => 'Europe/Paris' // Fuseau horaire de la réunion
  );

$datas = $zoom->createMeeting($userMailStd->mail, $meetingData);

if (! $datas['status']) {
  echo json_encode($datas);
  exit;
}

$attendees = ['attendees' => array()];

foreach ($users as $user)
  $attendees['attendees'][] = ['name' => $user->displayName];

$dataInviteLink = $zoom->inviteLink($datas['data']['id'], $attendees);

if (! $dataInviteLink['status']) {
  echo json_encode($dataInviteLink);
  exit;
}

echo json_encode(array_merge(['meetingData' => $datas], ['meetingAttendees' => $dataInviteLink]));
