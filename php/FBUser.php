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
    /**
     * @var string uid
     */
    private String $uid;

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
    private Sequence $sequence;

    /**
     * __construct
     *
     * @param string uid
     *
     * @return void
     */
    public function __construct(String $uid) {
        $this->uid = $uid;
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
    public static function factory(String $uid) : FBUser {
        if (!isset(self::$duration)) {
            throw new Exception("Le créneau doit être renseigné");
        }

        $fbUser = new self($uid);

        $fbUser->_selectFreebusy();
        $fbUser->_initSequence();
        $fbUser->_instanceCreneaux();

        return $fbUser;
    }

    private function _selectFreebusy() {

        $vcal = Vcalendar::factory()->parse($this->content);

//      TODO : Requête filtrant les sorties
//        $comps = $vcal->selectComponents(2023, 02, 24, 2023, 04, 24, "vfreebusy", false, true, true);

        $component = $vcal->getComponent();
        $this->fbusys = $component->getAllFreebusy();
        $this->vcal = $vcal;
    }

    private function _initSequence() : void {
        $duration = self::$duration;

        $sequence = FBUtils::createSequenceFromArrayFbusy($this->fbusys);
        // trie par date de début
        $this->sequence = FBUtils::sortSequence($sequence);
    }
    
    private function _instanceCreneaux() : void {

        $duration = self::getDuration();

        $isChanged = false;
        foreach ($this->sequence as $key => $period) {

            $periodDuration = $period->withDurationAfterStart($duration);
            
            $comparePeriod = $period->durationCompare($periodDuration);

            switch ($comparePeriod) {
                case 1:
                    # duration < creneau
                    $isChanged = true;
                    $this->_normCreneauxInferieurDuree($period, $key);
                    break;
                case -1:
                    # duration > creneau
                    $isChanged = true;
                    $this->sequence->remove($key);
                    break;
                case 0:
                    # duration == creneau
                    break;
                default:
                    throw new Exception("Erreur comparaison creneau _normCreneaux");
                    break;
            }
        }
        if ($isChanged) {
            $this->sequence = FBUtils::sortSequence($this->sequence);
        }
    }

    private function _normCreneauxInferieurDuree($periodToSplit, $indexSequence) : void {
        $duration = self::getDuration();

        $arrayNewPeriods = array();
        foreach ($periodToSplit->dateRangeForward($duration) as $datetime) {
            $endDate = $datetime->add($duration->dateInterval);
//          enlève 1 minute, IncludeStartExcludeEnd  $endDate = $datetime->add($duration->dateInterval)->sub(new DateInterval("PT1M"));

            $p = Period::fromDate($datetime, $endDate);
            $arrayNewPeriods[] = $p;
        }

        $this->sequence->remove($indexSequence);

        $indexNew = $indexSequence;
        foreach ($arrayNewPeriods as $newPeriod) {
            $this->sequence->insert($indexNew, $newPeriod);
            $indexNew++;
        }
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

}
