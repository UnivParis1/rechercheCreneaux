<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;
use DateTimeZone;
use League\Period\Sequence;

/**
 * Description of FBCompare
 *
 * @author ebohm
 */
class FBCompare
{

    public array $arrayFBUsers;
    private Sequence $creneauxGenerated;
    private Sequence $mergedBusys;
    private DateTimeZone $dateTimeZone;
    private int $nbcreneaux;
    private Sequence $creneauxFinaux;
    private int $nbResultatsAffichés;
    private array $arrayCreneauxAffiches;

    public function __construct($arrayFBUsers, Sequence &$creneauxGenerated, String $dtz, $nbcreneaux)
    {
        // supprime les fbusers fullbloquer ou optionnel
        foreach ($arrayFBUsers as $key => $fbUser)
            if ($fbUser->getEstFullBloquer() == true || $fbUser->estOptionnel == true)
                unset($arrayFBUsers[$key]);

        $this->arrayFBUsers = $arrayFBUsers;
        $this->creneauxGenerated = &$creneauxGenerated;
        $this->dateTimeZone = new DateTimeZone($dtz);
        $this->nbcreneaux = $nbcreneaux;
        $this->arrayCreneauxAffiches = array();
        $this->mergedBusys = $this->_getMergedBusysSequence();
        $this->creneauxFinaux = $this->_substractBusysFromCreneaux();
        $this->nbResultatsAffichés = $this->_calcNbResultats();
        $this->arrayCreneauxAffiches = $this->_arrayCreneauxAffiches();
    }

    public function getArrayCreneauxAffiches()
    {
        return $this->arrayCreneauxAffiches;
    }

    public function getNbResultatsAffichés()
    {
        return $this->nbResultatsAffichés;
    }

    private function _substractBusysFromCreneaux(): Sequence
    {
        $busySeq = $this->mergedBusys;
        $creneauxGenerated = $this->creneauxGenerated;

        $arr_creneaux = array();

        foreach ($creneauxGenerated as $creneau) {
            if ($this->_testPeriodsDebug($busySeq, $creneau) === false) {
                $arr_creneaux[] = $creneau;
            }
        }

        $seq = FBUtils::addTimezoneToLeaguePeriods($arr_creneaux, $this->dateTimeZone);

        return $seq;
    }

    private function _testPeriodsDebug($sequence, $periodToCompare)
    {
        foreach ($sequence as $period) {
            // optimisation performance
            if ( ! ($period->isDuring($periodToCompare) === false &&
                    $period->overlaps($periodToCompare) === false &&
                    $period->contains($periodToCompare) === false &&
                    $periodToCompare->isDuring($period) === false &&
                    $periodToCompare->overlaps($period) === false &&
                    $periodToCompare->contains($period) === false)) return true;
        }
        return false;
    }

    private function _mergeSequencesToArrayPeriods()
    {
        $arr_merged = array();
        foreach ($this->arrayFBUsers as $FBUser) {
            $arrayPeriods = $FBUser->getSequence()->jsonSerialize();
            $arr_diff = array_udiff($arrayPeriods, $arr_merged, function ($obj1, $obj2) {
                return $obj1 <=> $obj2;
            });
            $arr_merged = array_merge($arr_merged, $arr_diff);
        }

        return $arr_merged;
    }

    private function _getMergedBusysSequence(): Sequence
    {
        $array_periods = $this->_mergeSequencesToArrayPeriods();
        $seq = FBUtils::addTimezoneToLeaguePeriods($array_periods, $this->dateTimeZone);
        return $seq;
    }

    private function _calcNbResultats()
    {
        $sequence = $this->creneauxFinaux;
        $nbCreneaux = $this->nbcreneaux;

        $sizeFinal = $sequence->count();
        return ($nbCreneaux > $sizeFinal) ? $sizeFinal : $nbCreneaux;
    }

    private function _arrayCreneauxAffiches(): array
    {
        $sequence = $this->creneauxFinaux;
        $now = new DateTime('now', $this->dateTimeZone);

        $arrayCreneauxAffiches = array();
        foreach ($sequence as $period) {
            if ($period->startDate->getTimestamp() > $now->getTimestamp()) {
                $arrayCreneauxAffiches[] = $period;
            }
        }

        if (($nbcount = count($arrayCreneauxAffiches)) < $this->nbResultatsAffichés) {
            $this->nbResultatsAffichés = $nbcount;
        }

        return $arrayCreneauxAffiches;
    }

    public static function algo_search_results($fbUsers, $creneauxGenerated, $dtz, $nbcreneaux) {
        $returnStd = new stdClass();
        $returnStd->fbUsersUnsetted = array();
        $fbUsersCP = $fbUsers;

        for ($i = 0; $i < count($fbUsers); $i++) {

            $returnStd->fbUsersUnsetted[] = $fbUsers[$i];
            unset($fbUsersCP[$i]);

            $fbCompare = new FBCompare($fbUsersCP, $creneauxGenerated, $dtz, $nbcreneaux);

            if ($fbCompare->getNbResultatsAffichés() > 0 && count($fbUsersCP) > 0) {
                $returnStd->fbCompare = $fbCompare;
                return $returnStd;
            }
        }
        return null;
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
