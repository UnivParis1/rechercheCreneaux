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
        $fbUser->_initSequence();
        $fbUser->_instanceCreneaux();

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

    private function _initSequence() : void {
        $duration = self::$duration;

        $sequence = FBUtils::createSequenceFromArrayFbusy($this->fbusys, $this->getDateTimeZone());
        // trie par date de début
        $this->sequence = FBUtils::sortSequence($sequence);
    }
    
    private function _instanceCreneaux() : void {
        $duration = self::getDuration();
        $creneaugenSeq = $this->getCreneauxGenerated();

        foreach ($this->sequence as $busyPeriod) {
            $cmpBusyCreneau = FBUtils::_cmpSeqContainPeriod($creneaugenSeq, $busyPeriod);
            $cmpOverlapCreneau = FBUtils::_cmpSeqOverlapPeriod($creneaugenSeq, $busyPeriod);
            if ($cmpBusyCreneau  == 0 &&  $cmpOverlapCreneau == 0) {
                $this->_removePeriod($busyPeriod);
                continue;
            }

            die("work in progress");
            switch ($cmpOverlapCreneau) {
                case 1:
                    //to continue
                    exit;
                    break;
                case 2:
                    //to continue
                    exit;
                    break;
                default:
                    throw new Exception("Erreur comparaison creneau _normCreneaux");
            }

            switch ($cmpBusyCreneau) {
                case 1:
                    // creneau < busy
                    $this->_normCreneauxInferieurDuree($busyPeriod);
                    break;
                case -1:
                    // creneau > busy
                    $this->_normCreneauxSuperieurDuree($busyPeriod);
                    break;
                default:
                    throw new Exception("Erreur comparaison creneau _normCreneaux");
            }
        }
        if ($this->isChanged) {
            $this->sequence = FBUtils::sortSequence($this->sequence);
        }
    }

    private function _normCreneauxInferieurDuree(League\Period\Period $periodToSplit) : void {
        $creneauxGenerated = $this->getCreneauxGenerated();
        $sequence = $this->getSequence();
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
            $cmpIsInCreneau = FBUtils::_cmpSeqContainPeriod($creneauxGenerated, $newPeriod);
            if ($cmpIsInCreneau !== 0) {
                $sequence->insert($indexNew, $newPeriod);
                $indexNew++;
            }
        }

        $this->sequence = $sequence;
        $this->isChanged = true;
    }

    private function _normCreneauxSuperieurDuree(League\Period\Period $period) : void {
        $sequence = $this->getSequence();
        $idx = $sequence->indexOf($period);
        $duration = $this->getDuration();
        $sequence->remove($idx);
        $newPeriod = $period->withDurationAfterStart($duration);
        $sequence->insert($idx, $newPeriod);

        $this->sequence = $sequence;
        $this->isChanged = true;
    }

    private function _removePeriod(League\Period\Period $period) : void {
        $sequence = $this->getSequence();
        $idx = $sequence->indexOf($period);
        $sequence->remove($idx);
        $this->isChanged = true;
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
