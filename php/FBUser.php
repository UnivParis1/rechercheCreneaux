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
    private static String $url = "https://echange.univ-paris1.fr/kronolith/fb.php?u=";
                    
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

        $fd = fopen($this::$url . $uid, "r");

        $contents = '';

        while (!feof($fd)) {
            $contents .= fread($fd, 8192);
        }
        fclose($fd);

        $vcal = Vcalendar::factory();

        $vcal->parse($contents);

        $this->content = $contents;

        $this->fbusys = $vcal->getComponent()->getAllFreebusy();
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

        $fbUser->_initSequence();
        $fbUser->_instanceCreneaux();

        return $fbUser;
    }

    private function _initSequence() : void {
        $duration = self::$duration;
        $sequence = new Sequence();

        foreach ($this->fbusys as $fbusy) {

            $fbusdate = $fbusy[0];

            $t1DateTime = $fbusdate[0];
            $t2DateTime = $fbusdate[1];

            $t1DatePoint = DatePoint::fromDate($t1DateTime);
            $t2DatePoint = DatePoint::fromDate($t2DateTime);

            $period = Period::fromDate($t1DatePoint, $t2DatePoint);
            
            $sequence->push($period);
        }

        // trie par date de début
        $this->sequence = $this->_sortSequence($sequence);
    }
    
    private function _sortSequence($sequence) {
        $sequence->sort(function (Period $period1, Period $period2): int {
            return $period1->startDate <=> $period2->startDate;
        });
        
        return $sequence;
    }
    
    private function _instanceCreneaux() : void {

        $duration = self::getDuration();

        $index=0;
        $isChanged = false;
        foreach ($this->sequence as $period) {

            $periodDuration = $period->withDurationAfterStart($duration);
            
            $comparePeriod = $period->durationCompare($periodDuration);

            switch ($comparePeriod) {
                case 1:
                    # duration < creneau
                    $isChanged = true;
                    $this->_normCreneauxInferieurDuree($period, $index);
                    break;
                case -1:
                    # duration > creneau
                    $isChanged = true;
                    $this->sequence->remove($index);
                    break;
                case 0:
                    # duration == creneau
                    break;
                default:
                    throw new Exception("Erreur comparaison creneau _normCreneaux");
                    break;
            }
            $index++;
        }
        if ($isChanged) {
            $this->sequence = $this->_sortSequence($this->sequence);
        }
    }

    private function _normCreneauxInferieurDuree($periodToSplit, $indexSequence) : void {
        $duration = self::getDuration();

        $arrayNewPeriods = array();
        foreach ($periodToSplit->dateRangeForward($duration) as $datetime) {
            $endDate = $datetime->add($duration->dateInterval)->sub(new DateInterval("PT1M"));

            $p = Period::fromDate($datetime, $endDate);
            $arrayNewPeriods[] = $p;
        }

        $this->sequence->remove($indexSequence);

        $indexNew = $indexSequence;
        foreach ($arrayNewPeriods as $newPeriod) {
            $this->sequence->insert($indexSequence, $newPeriod);
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
