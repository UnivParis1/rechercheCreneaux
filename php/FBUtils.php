<?php

use League\Period\Period;
use League\Period\Sequence;
use League\Period\DatePoint;
use League\Period\Chart;
use RRule\RRule;


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
        $sequence->sort(function (Period $period1, Period $period2): int {
            if ($period1->startDate == $period2->startDate && $period1->endDate == $period2->endDate) {
                $sequence->remove($period2);
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

    private static function generateCreneaux($dtstart, $until, $duree, $hours, $days = ['MO', 'TU', 'WE', 'TH', 'FR']) {
        $r = new RRule([
            'FREQ' => 'MINUTELY',
            'DTSTART' => $dtstart, // '2023-07-12'
            'UNTIL' => $until, // '2023-10-23'
            'INTERVAL' => $duree,
            'BYHOUR' => $hours,
            'BYDAY' => $days
        ]);

        return $r->getOccurrences();
    }

    public static function getDefaultsCreneaux($dureeEnMinutes, $hours = [9, 10, 11, 14, 15, 16], $addXmonth = 1) {
        $dateBeginCreneau = date('Y-m-d');
        $dateEndCreneau = DateTime::createFromFormat('Y-m-d', $dateBeginCreneau)
                ->add(new DateInterval('P'. $addXmonth .'M'))
                ->format('Y-m-d');

        return self::generateCreneaux($dateBeginCreneau, $dateEndCreneau, $dureeEnMinutes, $hours);
    }

    public static function parsePlagesHoraires(array $plagesHoraires) {
        $arrHours = array();
        foreach ($plagesHoraires as $plages) {
            $pt = explode('-', $plages);
            for ($i = (int) $pt[0]; $i < $pt[1]; $i++)
                $arrHours[] = $i;
        }
        return $arrHours;
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

    public static function _cmpSeqOverlapPeriod(League\Period\Sequence $sequence, League\Period\Period $periodToCompare ) : int {
        foreach ($sequence as $period) {

            $test1 = $period->overlaps($periodToCompare);
            $test2 = $periodToCompare->overlaps($period);
            // creneau > busy | creneau déborde sur busy
            if ($test1) {
                return -1;
            }elseif ($test2) {// busy > creneau | busy déborde sur créneau
                die("tmp1");
                return 1;
            }
        }
        return 0;
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
}
