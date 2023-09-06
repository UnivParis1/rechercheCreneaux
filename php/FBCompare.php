<?php

use League\Period\DatePoint;
use League\Period\Period;
use League\Period\Duration;
use League\Period\Sequence;

/**
 * Description of FBCompare
 *
 * @author ebohm
 */
class FBCompare {

    private Array $arrayFBUsers;
    private Sequence $creneauxGenerated;
    private Sequence $mergedBusys;
    private DateTimeZone $dateTimeZone;

    public function __construct(&$arrayFBUsers, League\Period\Sequence &$creneauxGenerated, $dtz) {
        $this->arrayFBUsers =& $arrayFBUsers;
        $this->creneauxGenerated =& $creneauxGenerated;
        $this->dateTimeZone = new DateTimeZone($dtz);
        $this->mergedBusys = $this->_getMergedBusysSequence();
    }

    public function substractBusysFromCreneaux() {
        $busySeq = $this->mergedBusys;
        $creneauxGenerated = $this->creneauxGenerated;

        $arr_creneaux = array();

        foreach ($creneauxGenerated as $creneau) {
            if ($this->_testPeriodsDebug($busySeq, $creneau) == false) {
                $arr_creneaux[] = $creneau;
            }
        }

        $seq = FBUtils::addTimezoneToLeaguePeriods($arr_creneaux, $this->dateTimeZone);

        return $seq;
    }

    private function _testPeriodOverlaps($sequence, $periodToCompare) {
        foreach ($sequence as $period) {
            $arr = array();

            if ($period->overlaps($periodToCompare) || $periodToCompare->overlaps($period)) {
                return true;
            }
        }
        return false;
    }


    private function _testPeriodsDebug($sequence, $periodToCompare) {
        foreach ($sequence as $period) {
            $arr = array();
            $arr[] = $period->isDuring($periodToCompare);
            $arr[] = $period->overlaps($periodToCompare);
            $arr[] = $period->contains($periodToCompare);

            $arr[] = $periodToCompare->isDuring($period);
            $arr[] = $periodToCompare->overlaps($period);
            $arr[] = $periodToCompare->contains($period);

            foreach ($arr as $a) {
                if ($a) {
//                    die(var_dump($arr));
                    return true;
                }
            }
        }
        return false;
    }

    private function _mergeSequencesToArrayPeriods() {
        $arrayPeriodsIntersected = $this->arrayFBUsers[0]->getSequence()->jsonSerialize();

        $arr_merged = array();
        foreach ($this->arrayFBUsers as $FBUser) {
            $arrayPeriods = $FBUser->getSequence()->jsonSerialize();
            $arr_diff = array_udiff($arrayPeriods, $arr_merged, function($obj1, $obj2) {
                return $obj1 <=> $obj2;
            });
            $arr_merged = array_merge($arr_merged, $arr_diff);
        }

        return $arr_merged;
    }

    private function _getMergedBusysSequence() : League\Period\Sequence {
        $array_periods = $this->_mergeSequencesToArrayPeriods();
        $seq = FBUtils::addTimezoneToLeaguePeriods($array_periods, $this->dateTimeZone);
        return $seq;
    }


/*     public function compareSequences() {
        $arrayPeriodsIntersected = $this->arrayFBUsers[0]->getSequence()->jsonSerialize();
        foreach ($this->arrayFBUsers as $FBUser) {
            $arrayPeriods = $FBUser->getSequence()->jsonSerialize();
            $arrayPeriodsIntersected = $this->_intersectArrayPeriod($arrayPeriodsIntersected, $arrayPeriods);
        }
        return $arrayPeriodsIntersected;
    }
 */

/*     private function _intersectArrayPeriod(array $arrayPeriod1, array $arrayPeriod2) {
        $intersection = array_uintersect($arrayPeriod1, $arrayPeriod2, function($obj1, $obj2) {
                return $obj1<=>$obj2;
        });
        return $intersection;
    }
 */

}
