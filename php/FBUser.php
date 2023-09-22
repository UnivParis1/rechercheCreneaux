<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\VcalendarParser;
use Kigkonsult\Icalcreator\Vfreebusy;
use League\Period\DatePoint;
use League\Period\Period;
use League\Period\Duration;
use League\Period\Sequence;

/**
 * Description of FBUser
 *
 * @author ebohm
 */
class FBUser {

    /**
     * @var string url
     */
    private static String $url;

    /**
     * @var Duration duration
     */
    private static Duration $duration;

    private static League\Period\Sequence $creneauxGenerated;

    /**
     * @var string uid
     */
    private String $uid;

    /**
     * @var string dtz
     */
    private String $dtz;

    private DateTimeZone $dateTimeZone;

    /**
     * @var string content
     */
    private String $content;

    /**
     * @var Vcalendar vcal
     */
    private Vcalendar $vcal;
    /**
     * @var array fbusys
     */
    private Array $fbusys;
    
    /**
     * @var Sequence sequence
     */
    private ?Sequence $sequence;

    private bool $isChanged;

    /**
     * __construct
     *
     * @param string uid
     *
     * @return void
     */
    private function __construct(String $uid, String $dtz, String $url) {
        $this->uid = $uid;
        $this->dtz = $dtz;
        $this->setDateTimeZone($dtz);
        $this::$url = $url;
        $this->isChanged = false;

        $contents = '';

        $fd = fopen($this::$url . $uid, "r");
        while (!feof($fd)) {
            $contents .= fread($fd, 8192);
        }
        fclose($fd);

        $this->content = $contents;
    }

    /**
     * factory
     *
     * @param string uid
     *
     * @return FBUser
     */
    public static function factory(String $uid, String $dtz, String $url, $dureeEnMinutes, &$creneaux) : FBUser {
        if (!isset(self::$duration))
            self::setDuration($dureeEnMinutes);
        if (!isset(self::$creneauxGenerated))
            self::setCreneauxGenerated($creneaux);

        $fbUser = new self($uid, $dtz, $url);

        $fbUser->_selectFreebusy();
        $sequence = $fbUser->_initSequence();
        $fbUser->sequence = $fbUser->_instanceCreneaux($sequence);
        return $fbUser;
    }

    private function _selectFreebusy() {

        $vcal = Vcalendar::factory()->parse($this->content);

        if ($vcal->countComponents() !== 1) {
            throw new Exception("FBUser: component !== 1");
        }

        $component = $vcal->getComponent();
        $fbusys = $component->getAllFreebusy();

        $this->fbusys = $fbusys;
        $this->vcal = $vcal;
    }

    private function _initSequence() : Sequence {
        $duration = self::$duration;

        $sequence = FBUtils::createSequenceFromArrayFbusy($this->fbusys, $this->getDateTimeZone());
        // trie par date de début
        $sequence = FBUtils::sortSequence($sequence);

        return $sequence;
    }

    private function _instanceCreneaux(&$sequence) : Sequence {
        $duration = self::getDuration();
        $creneaugenSeq = $this->getCreneauxGenerated();

        foreach ($sequence as $busyPeriod) {

            $busyInclus = $this->_instanceCreneauxBusysInclus($creneaugenSeq, $busyPeriod, $sequence);

            if ($sequence->indexOf($busyPeriod) !== false)
                $busyOverlap = $this->_instanceCreneauxBusysOverlap($creneaugenSeq, $busyPeriod, $sequence);
            else
                $busyOverlap = false;

            // Suppression des busys lorsqu'ils sont hors des periodes générées
            if ($busyInclus === false && $busyOverlap === false) {
                $sequence = $this->_removePeriod($busyPeriod, $sequence);
                continue;
            }
        }
        if ($this->isChanged) {
            $sequence = FBUtils::sortSequence($sequence);
        }
        return $sequence;
    }

    /**
     * _instanceCreneauxBusysOverlap
     *
     * Méthode servant à tester les cas où un des créneaux généré enchevêtre une période d'un busy et réciproquement
     *
     * @param  mixed $creneaugenSeq
     * @param  mixed $busyPeriod
     * @return bool
     */
    private function _instanceCreneauxBusysOverlap(League\Period\Sequence $creneaugenSeq, League\Period\Period $busyPeriod, &$sequence) : bool {
        $cmpOverlapCreneau = FBUtils::_cmpSeqOverlapPeriod($creneaugenSeq, $busyPeriod);

        if ($cmpOverlapCreneau) {
            $arrayIdxGen = FBUtils::_cmpGetIdxOverlapCreneauBusy($creneaugenSeq, $busyPeriod);
            $sequence = $this->_replaceWithArrayCreneauxGeneratedIdx($busyPeriod, $arrayIdxGen, $sequence);
        }
        return false;
    }

    private function _replaceWithArrayCreneauxGeneratedIdx($busyPeriod, $arrayIdxGen, &$sequence) : Sequence {
        $creneauxGenerated = $this->getCreneauxGenerated();

        $offset = $sequence->indexOf($busyPeriod);
        $sequence->remove($offset);

        $indexNew = $offset;
        foreach ($arrayIdxGen as $idxCreneauxGen) {
            $newPeriod = clone ($creneauxGenerated->get($idxCreneauxGen));
            $sequence->insert($indexNew, $newPeriod);
            $indexNew++;
        }

        return $sequence;
    }

    /**
     * _instanceCreneauxBusysInclus
     *
     * Méthode servant à tester les cas où un des créneaux généré inclus une période d'un busy et réciproquement
     *
     * @param  mixed $creneaugenSeq
     * @param  mixed $busyPeriod
     * @return bool
     */
    private function _instanceCreneauxBusysInclus(League\Period\Sequence $creneaugenSeq, League\Period\Period $busyPeriod, &$sequence) : bool {
        $cmpBusyCreneau = FBUtils::_cmpSeqContainPeriod($creneaugenSeq, $busyPeriod);

        if ($cmpBusyCreneau === 0) {
            return false;
        }

        switch ($cmpBusyCreneau) {
            case 1:
                // creneau < busy
                $sequence = $this->_normCreneauxInferieurDuree($busyPeriod, $sequence);
                break;
            case -1:
                // creneau > busy
                $sequence = $this->_normCreneauxSuperieurDuree($busyPeriod, $sequence);
                break;
            default:
                throw new Exception("Erreur comparaison creneau _normCreneaux");
        }

        return true;
    }

    private function _normCreneauxInferieurDuree(League\Period\Period $periodToSplit, &$sequence) : Sequence {
        $creneauxGenerated = $this->getCreneauxGenerated();
        $offset = $sequence->indexOf($periodToSplit);
        $duration = self::getDuration();

        $arrayNewPeriods = array();
        foreach ($periodToSplit->dateRangeForward($duration) as $datetime) {
            $endDate = $datetime->add($duration->dateInterval);
            $p = Period::fromDate($datetime, $endDate);
            $arrayNewPeriods[] = $p;
        }

        $sequence->remove($offset);

        $indexNew = $offset;
        foreach ($arrayNewPeriods as $newPeriod) {
            // vérifie si les busys normalisés sont bien dans les creneaux au cas ou la periode > plusieurs jours
            $testInCreneau = FBUtils::_cmpSeqContainPeriod($creneauxGenerated, $newPeriod);
            $testOverlap = FBUtils::_cmpSeqOverlapPeriod($creneauxGenerated, $newPeriod);

//            if ($testInCreneau !== 0) { // || $testOverlap === false) {
                $sequence->insert($indexNew, $newPeriod);
                $indexNew++;
//            }
        }
        $this->isChanged = true;
        return $sequence;
    }

    private function _normCreneauxSuperieurDuree(League\Period\Period $period, &$sequence) : Sequence {
        $idx = $sequence->indexOf($period);
        $duration = $this->getDuration();
        $sequence->remove($idx);
        $newPeriod = $period->withDurationAfterStart($duration);
        $sequence->insert($idx, $newPeriod);
        $this->isChanged = true;
        return $sequence;
    }

    private function _removePeriod(League\Period\Period $period, &$sequence) : Sequence {
        $idx = $sequence->indexOf($period);
        $sequence->remove($idx);
        $this->isChanged = true;
        return $sequence;
    }

    /**
     * Get creneauMinute
     *
     * @return  Duration
     */
    public static function getDuration() : Duration {
        return self::$duration;
    }
    
    /**
     * setDuration
     *
     * @param int dureeMinutes
     *
     * @return void
     */
    public static function setDuration(int $dureeMinutes) : void {
        $dateInterval = new DateInterval("PT".$dureeMinutes."M");
        self::$duration = Duration::fromDateInterval($dateInterval);
    }

    public static function setUrl(String $urlSet) : void {
        self::$url = $urlSet;
    }

    /**
     * Get sequence
     *
     * @return  Sequence
     */ 
    public function getSequence()
    {
        return $this->sequence;
    }

    protected function setSequence(&$sequence) {
        $this->sequence = $sequence;
    }

    public function getDateTimeZone()
    {
        return $this->dateTimeZone;
    }

    public function setDateTimeZone(String $dtz)
    {
        $this->dateTimeZone = new DateTimeZone($dtz);
    }

    /**
     * Get the value of creneauxGenerated
     */
    public function getCreneauxGenerated() : League\Period\Sequence
    {
        if (isset(self::$creneauxGenerated))
            return self::$creneauxGenerated;
        else
            return false;
    }

    /**
     * Set the value of creneauxGenerated
     *
     * @return  self
     */
    public static function setCreneauxGenerated(&$creneauxGenerated)
    {
        self::$creneauxGenerated =& $creneauxGenerated;

        return self::$creneauxGenerated;
    }
}
