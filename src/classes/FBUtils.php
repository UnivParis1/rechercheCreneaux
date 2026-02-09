<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use League\Period\Period;
use League\Period\Sequence;
use RechercheCreneaux\FBRessource;


/**
 * Classe regroupant les fonctions utils pouvant être utilisés dans plusieurs classes
 * Principalement les callbacks de tri sur les sequences
 */
class FBUtils {

    public static function sortSequence(Sequence &$sequence) : Sequence {
        $sequence->sort(function (Period $period1, Period $period2) : int {
            if ($period1->startDate == $period2->startDate && $period1->endDate == $period2->endDate) {
                return 0;
            }
            return $period1->startDate <=> $period2->startDate; 
        });

        return $sequence;
    }

    /**
     * Fonction qui trie les FBUsers selon le nombre de busys brut renvoyés par le webservice
     *
     * Le trie s'effectue sur le nombre de créneau busy qui sont renvoyés par le webservice
     * Ceci ne prend pas en compte les busy générés par l'application elle même mais uniquement les busys
     * qui sont renvoyés par l'appel à l'api
     *
     * @param  array[FBUser]
     *
     * @return array[FBUser]
     */
    public static function sortFBUsersByBusyCount(FBRessource ... $fbUsers) : array {
        $fbUserSort = $fbUsers;
        usort($fbUserSort, function(FBRessource $fbusr1, FBRessource $fbusr2)  {
            if (!isset($fbusr1->fbusys) || !isset($fbusr2->fbusys)) {
                return null;
            }
            $nbBusys1 = count($fbusr1->fbusys);
            $nbBusys2 = count($fbusr2->fbusys);

            return $nbBusys1 <=> $nbBusys2;
        });
        return $fbUserSort;
    }

    /**
     * @param array{array{DateTime[]}} $fbusys
     * @param DateTimeZone $dtz
     *
     * @return Sequence
     */
    public static function createSequenceFromArrayFbusy(array $fbusys, DateTimeZone $dtz) : Sequence {
        
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
    public static function createSequenceFromArrayPeriods(array $periods) : Sequence {
        $sequence = new Sequence();

        foreach ($periods as $period) {
            $sequence->push($period);
        }

        return $sequence;
    }
    
    /**
     * @param array{DateTime} $creneaux
     * @param int $duree
     *
     * @return Sequence
     */
    public static function createSequenceFromDT(array $creneaux, int $duree) : Sequence {

        $seqgen = new Sequence();

        foreach ($creneaux as $creneau) {
            $dateend = DateTimeImmutable::createFromMutable($creneau);
            $end = $dateend->add(new DateInterval('PT'. $duree . 'M'));
            $period = Period::fromDate($creneau, $end);

            $seqgen->push($period);
        }

        return $seqgen;
    }

    /**
     * @param array{Period} $periods
     * @param DateTimeZone $dateTimeZone
     * 
     * @return Sequence
     */    public static function addTimezoneToLeaguePeriods(array $periods, DateTimeZone $dateTimeZone) : Sequence {
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

    public static function _cmpSeqContainPeriod(Sequence $creneaugenSeq, Period $periodToCompare ) : int {
        foreach ($creneaugenSeq as $period) {
            // creneau > busy
            if ($period->contains($periodToCompare)) {
                return -1;
            }elseif ($periodToCompare->contains($period)) {// creneau < busy
                return 1;
            }
        }
        return 0;
    }

    public static function _cmpSeqOverlapPeriod(Sequence $creneaugenSeq, Period $periodToCompare ) : bool {
        foreach ($creneaugenSeq as $period) {
            if ($period->overlaps($periodToCompare)) {
                return true;
            }
        }
        return false;
    }

    public static function _cmpGetIdxOverlapCreneauBusy(Sequence $creneaugenSeq, Period $periodToCompare ) : array {
        $array = array();
        foreach ($creneaugenSeq as $period) {
            $testDebut = $period->overlaps($periodToCompare);
            if ($testDebut) {
                $array[] = $creneaugenSeq->indexOf($period);
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

    public static function getMailsSended(array $aMails) : array {
        $a = array();
        foreach ($aMails as $aMail) {
            if ($aMail['sended']) {
                $a[] = $aMail;
            }
        }
        return $a;
    }

    public static function formTooltipEnvoyéHTML(array $aMails) : string {
        $html = "<span>Mails envoyés à : </span><br />";
        $idx=0;
        foreach ($aMails as $aMail) {
            $html .= (($idx == 0) ? "":", ") . $aMail[1]['displayName'];
            $idx++;
        }
        return $html;
    }
}
