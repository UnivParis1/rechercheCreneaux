<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use Exception;
use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use League\Period\Chart;
use League\Period\Period;
use League\Period\Sequence;
use RechercheCreneaux\FBUser;
use Kigkonsult\Icalcreator\Vcalendar;


/**
 * Description of FBUtils
 *
 * @author ebohm
 */
class FBUtils {

    public static function drawSequence(array $periods) {
        
        $sequence = FBUtils::createSequenceFromArrayPeriods($periods);
        $sequence = FBUtils::sortSequence($sequence);

        $dataset = new Chart\Dataset([['period', $sequence->length()], ['sequence', $sequence]]);
        (new Chart\GanttChart())->stroke($dataset);
    }
    
    public static function sortSequence(Sequence &$sequence) {
        $sequence->sort(function (Period $period1, Period $period2) : int {
            if ($period1->startDate == $period2->startDate && $period1->endDate == $period2->endDate) {
                return 0;
            }
            return $period1->startDate <=> $period2->startDate; 
        });

        return $sequence;
    }

    public static function sortFBUsersByBusyCount(FBUser ... $fbUsers) : Array {
        $fbUserSort = $fbUsers;
        usort($fbUserSort, function(FBUser $fbusr1, FBUser $fbusr2)  {
            $cmp = $fbusr1->getSequence()->count() <=> $fbusr2->getSequence()->count();
            return $cmp;
        });
        return $fbUserSort;
    }

    public static function createSequenceFromArrayFbusy(array $fbusys, $dtz) {
        
        $sequence = new Sequence();
        
        foreach ($fbusys as $fbusy) {
            $fbusdate = $fbusy[0];

            $dstart = DateTimeImmutable::createFromMutable($fbusdate[0]);
            $dstart = $dstart->setTimezone($dtz);
            $dend = DateTimeImmutable::createFromMutable($fbusdate[1]);
            $dend = $dend->setTimezone($dtz);

            $period = Period::fromDate($dstart, $dend);

            $sequence->push($period);
        }

        return $sequence;
    }
        
    /**
     * createSequenceFromArrayPeriods
     *
     * @param  array{Period} $periods
     * @return Sequence $sequence
     */
    public static function createSequenceFromArrayPeriods($periods) {
        $sequence = new Sequence();

        foreach ($periods as $period) {
            $sequence->push($period);
        }

        return $sequence;
    }
    
    public static function createSequenceFromDT(array $creneaux, $duree) {

        $seqgen = new Sequence();

        foreach ($creneaux as $creneau) {
            $dateend = DateTimeImmutable::createFromMutable($creneau);
            $end = $dateend->add(new DateInterval('PT'. $duree . 'M'));
            $period = Period::fromDate($creneau, $end);

            $seqgen->push($period);
        }

        return $seqgen;
    }

    public static function addTimezoneToLeaguePeriods($periods, DateTimeZone $dateTimeZone) {
        $seq = new Sequence();

        foreach ($periods as $period) {
            $dstart = DateTime::createFromImmutable($period->startDate);
            $dstart->setTimezone($dateTimeZone);
            $dend = DateTime::createFromImmutable($period->endDate);
            $dend->setTimezone($dateTimeZone);

            $newPeriod = Period::fromDate($dstart, $dend);
            $seq->push($newPeriod);
        }
        return $seq;
    }

    public static function _cmpSeqContainPeriod(Sequence $sequence, Period $periodToCompare ) : int {
        foreach ($sequence as $period) {
            // creneau > busy
            if ($period->contains($periodToCompare)) {
                return -1;
            }elseif ($periodToCompare->contains($period)) {// creneau < busy
                return 1;
            }
        }
        return 0;
    }

    public static function _cmpSeqOverlapPeriod(Sequence $sequence, Period $periodToCompare ) : bool {
        foreach ($sequence as $period) {
            if ($period->overlaps($periodToCompare)) {
                return true;
            }
        }
        return false;
    }

    public static function _cmpGetIdxOverlapCreneauBusy(Sequence $sequence, Period $periodToCompare ) : array {
        $array = array();
        foreach ($sequence as $period) {
            $testDebut = $period->overlaps($periodToCompare);
            if ($testDebut) {
                $array[] = $sequence->indexOf($period);
            }
        }
        return $array;
    }

    public static function getIdxCreneauxWithStartEnd(array $sessionDate, $dtStart, $dtEnd) : int {
        foreach ($sessionDate as $key => $data) {
            $modalCreneau = $data['modalCreneau'];
            $startCurrent = new DateTime($modalCreneau['modalCreneauStart']);
            $endCurrent = new DateTime($modalCreneau['modalCreneauEnd']);

            if ($dtStart == $startCurrent && $dtEnd == $endCurrent)
                return $key;
        }

        return -1;
    }

    /**
     * requestUidInfo
     *
     * @param  string $uid
     * @param  string $urlwsgroup
    * return stdObj->uid, stdObj->displayName, stdObj->mail
     * @return stdClass
     */
    public static function requestUidInfo($uid, $urlwsgroup) {
        $fd = fopen($urlwsgroup . '?token='. $uid . '&maxRows=1&attrs=uid,displayName,mail', 'r');
        $ajaxReturn = stream_get_contents($fd);
        fclose($fd);

        $arrayReturn = json_decode($ajaxReturn);

        $exMsg = "Erreur fonction requestUidInfo";
        if (!$ajaxReturn[0])
            throw new Exception($exMsg . "uid: $uid");

        foreach ($arrayReturn as $stdObj) {
            if ($stdObj->uid == $uid)
                return $stdObj;
        }
        throw new Exception($exMsg);
    }

    public static function icalCreationInvitation($listUserinfos, $start, $end, $titleEvent, $descriptionEvent, $lieuEvent, $dtz) {
        $uidFirst = array_key_first($listUserinfos);
        $vcalendar = Vcalendar::factory()
            ->setMethod( Vcalendar::REQUEST )
            ->setXprop( Vcalendar::X_WR_CALNAME, $listUserinfos[$uidFirst]['displayName'] )
            ->setXprop( Vcalendar::X_PROP, "Application Recherche créneaux" )
            ->setXprop( Vcalendar::X_WR_TIMEZONE, $dtz );

        $event1 = $vcalendar->newVevent()
            ->setTransp( Vcalendar::OPAQUE )
            ->setSummary( $titleEvent )
            ->setDescription($descriptionEvent)
            ->setLocation( $lieuEvent)
            // set the time
            ->setDtstart(new DateTime($start,new DateTimezone($dtz)))
            ->setDtend(new DateTime($end,new DateTimezone($dtz)))
            ->setOrganizer($listUserinfos[$uidFirst]['mail'],
                [ Vcalendar::CN =>  $listUserinfos[$uidFirst]['displayName']]
            );

        foreach ($listUserinfos as $userinfo) {
            $event1->setAttendee($userinfo['mail'],
                [Vcalendar::ROLE     => Vcalendar::REQ_PARTICIPANT,
                Vcalendar::PARTSTAT => Vcalendar::NEEDS_ACTION,
                Vcalendar::RSVP     => Vcalendar::TRUE]);
        }

        $event1->setStatus(Vcalendar::CONFIRMED);
        $event1 = $event1->setClass('PUBLIC');
        $valarm = $event1->newValarm(\Kigkonsult\Icalcreator\IcalInterface::DISPLAY, '-PT120M');
        $valarm->setDescription($descriptionEvent);

        return $vcalendar->vtimezonePopulate()->createCalendar();
    }

    public static function getMailsSended(array $aMails) {
        $a = array();
        foreach ($aMails as $aMail) {
            if ($aMail['sended']) {
                $a[] = $aMail;
            }
        }
        return $a;
    }

    public static function formTooltipEnvoyéHTML(array $aMails) {
        $html = "<span>Mails envoyés à : </span><br />";
        $idx=0;
        foreach ($aMails as $aMail) {
            $html .= (($idx == 0) ? "":", ") . $aMail[1]['displayName'];
            $idx++;
        }
        return $html;
    }
}
