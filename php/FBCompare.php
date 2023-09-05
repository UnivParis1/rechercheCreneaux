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

    public function __construct(&$arrayFBUsers, League\Period\Sequence &$creneauxGenerated) {
        $this->arrayFBUsers = $arrayFBUsers;
        $this->creneauxGenerated = $creneauxGenerated;
        $this->mergedBusys = $this->_getMergedBusysSequence();
    }

    public function substractBusysFromCreneaux() {
        $creneauxFinaux = $this->creneauxGenerated->subtract($this->mergedBusys);

        return $creneauxFinaux;
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
        $seq = FBUtils::addTimezoneToLeaguePeriods($array_periods, new DateTimeZone(date_default_timezone_get()));
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
