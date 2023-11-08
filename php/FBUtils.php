<?php

use League\Period\Period;
use League\Period\Sequence;
use League\Period\DatePoint;
use League\Period\Chart;
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
    
    public static function createSequenceFromArrayFbusy(array $fbusys, $dtz) {
        
        $sequence = new Sequence();
        
        foreach ($fbusys as $fbusy) {
            
            $fbusdate = $fbusy[0];

            $t1DateTime = $fbusdate[0];
            $t2DateTime = $fbusdate[1];

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
     * @param  array{League\Period\Period} $periods
     * @return League\Period\Sequence $sequence
     */
    public static function createSequenceFromArrayPeriods($periods) {
        $sequence = new Sequence();

        foreach ($periods as $period) {
            $sequence->push($period);
        }

        return $sequence;
    }
    
    public static function createSequenceFromDT(array $creneaux, $duree) {

        $seqgen = new \League\Period\Sequence();

        foreach ($creneaux as $creneau) {
            $dateend = DateTimeImmutable::createFromMutable($creneau);
            $end = $dateend->add(new DateInterval('PT'. $duree . 'M'));
            $period = \League\Period\Period::fromDate($creneau, $end);

            $seqgen->push($period);
        }

        return $seqgen;
    }

    public static function addTimezoneToLeaguePeriods($periods, DateTimeZone $dateTimeZone) {
        $seq = new \League\Period\Sequence();

        foreach ($periods as $period) {
            $dstart = DateTime::createFromImmutable($period->startDate);
            $dstart->setTimezone($dateTimeZone);
            $dend = DateTime::createFromImmutable($period->endDate);
            $dend->setTimezone($dateTimeZone);

            $newPeriod = \League\Period\Period::fromDate($dstart, $dend);
            $seq->push($newPeriod);
        }
        return $seq;
    }

    public static function _cmpSeqContainPeriod(League\Period\Sequence $sequence, League\Period\Period $periodToCompare ) : int {
        foreach ($sequence as $period) {

            $test1 = $period->contains($periodToCompare);
            $test2 = $periodToCompare->contains($period);
            // creneau > busy
            if ($test1) {
                return -1;
            }elseif ($test2) {// creneau < busy
                return 1;
            }
        }
        return 0;
    }

    public static function _cmpSeqOverlapPeriod(League\Period\Sequence $sequence, League\Period\Period $periodToCompare ) : bool {
        foreach ($sequence as $period) {
            if ($period->overlaps($periodToCompare)) {
                return true;
            }
        }
        return false;
    }

    public static function _cmpGetIdxOverlapCreneauBusy(League\Period\Sequence $sequence, League\Period\Period $periodToCompare ) : array {
        $array = array();
        foreach ($sequence as $period) {
            $testDebut = $period->overlaps($periodToCompare);
            if ($testDebut) {
                $array[] = $sequence->indexOf($period);
            }
        }
        return $array;
    }

    public static function getIdxCreneauxWithStartEnd(array $listDate, $start, $end) : int {
        $dtStart = new DateTime($start);
        $dtEnd = new DateTime($end);

        for ($idx = 0; $idx < sizeof($listDate); $idx++) {
            $date = $listDate[$idx];
            if ($date->startDate == $dtStart && $date->endDate == $dtEnd) {
                return $idx;
            }
        }
        return -1;
    }

    public static function requestUidsNames($listUids, $url) {
        $arrayReturn = array();

        foreach ($listUids as $uid) {
            $fp = fopen($url . "?token=$uid&maxRows=1&attrs=uid,displayName,mail", 'r');
            $response = fread($fp, 1024);
            fclose($fp);

            $reqArray = json_decode($response);

            if (sizeof($reqArray) == 1) {
                array_push($arrayReturn, get_object_vars($reqArray[0]));
            }
        }
        return $arrayReturn;
    }

    public static function icalCreationInvitation($listUserinfos, $start, $end, $titleEvent, $descriptionEvent, $lieuEvent, $dtz) {
        $vcalendar = Vcalendar::factory()
            ->setMethod( Vcalendar::REQUEST )
            ->setXprop( Vcalendar::X_WR_CALNAME, $listUserinfos[0]['displayName'] )
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
            ->setOrganizer($listUserinfos[0]['mail'],
                [ Vcalendar::CN =>  $listUserinfos[0]['displayName']]
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
}
