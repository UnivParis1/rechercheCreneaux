<?php

declare(strict_types=1);

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

use Kigkonsult\Icalcreator\Vcalendar;
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
    public String $uid;

    private stdClass $uidInfos;

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


    // sert à déterminer si l'agenda d'une personne est bloquée
    private bool $estFullBloquer = false;

    // si son agenda n'est pas pris en compte dans les résultats
    // dans le cas où la recherche donne 0 résultats, on élimine les agendas les
    // plus chargés
    public bool $estDisqualifier = false;

    public bool $estOptionnel;

    /**
     * __construct
     *
     * @param string uid
     *
     * @return void
     */
    private function __construct(String $uid, String $dtz, String $url, $estOptionnel = false) {
        $this->uid = $uid;
        $this->dtz = $dtz;
        $this->setDateTimeZone($dtz);
        $this::$url = $url;
        $this->isChanged = false;
        $this->uidInfos = $this->_getUidInfos($uid);
        $this->estOptionnel = $estOptionnel;

        $fd = fopen($this::$url . $uid, "r");
        $content = stream_get_contents($fd);
        fclose($fd);

        $this->content = $content;
    }

    /**
     * factory
     *
     * @param string uid
     *
     * @return FBUser
     */
    public static function factory(String $uid, String $dtz, String $url, $dureeEnMinutes, &$creneaux, $estOptionnel = false) : FBUser {
        if (!isset(self::$duration))
            self::setDuration($dureeEnMinutes);
        if (!isset(self::$creneauxGenerated))
            self::setCreneauxGenerated($creneaux);

        $fbUser = new self($uid, $dtz, $url, $estOptionnel);

        $fbUser->_selectFreebusy();
        $sequence = $fbUser->_initSequence();

// commenté temporairement, pour tester algo suppression d'une personne
        if ($fbUser->_testSiAgendaBloque($sequence))
            $fbUser->estFullBloquer = true;

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
            return true;
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
            $sequence->insert($indexNew, $newPeriod);
            $indexNew++;
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

    private function _testSiAgendaBloque(Sequence &$sequence) {

        $testFBUserclone = clone($this);
        $seqToTest = clone($sequence);

        // generation de créneaux standards

        $creneauxGeneratedTest = (new FBCreneauxGeneres(date('Y-m-d'), 60, array('9-12', '14-17'), $this->dateTimeZone->getName()))->getCreneauxSeq();

        $testFBUserclone->setCreneauxGenerated($creneauxGeneratedTest);
        $seq = $testFBUserclone->_instanceCreneaux($seqToTest);
        $testFBUserclone->setSequence($seq);

        $fbUsersTest = array($testFBUserclone);
        $fbCompareTest = new FBCompare($fbUsersTest, $creneauxGeneratedTest, $this->dateTimeZone->getName(), 1);

        $testCompare = $fbCompareTest->getNbResultatsAffichés();

        if ($testCompare == 0) {
            $this->estFullBloquer = true;
            return true;
        }
        return false;
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
     * @return  League\Period\Sequence
     */
    public static function setCreneauxGenerated(&$creneauxGenerated)
    {
        self::$creneauxGenerated =& $creneauxGenerated;

        return self::$creneauxGenerated;
    }

    public function getEstFullBloquer() {
        return $this->estFullBloquer;
    }

    public function getEstOptionnel() {
        return $this->estOptionnel;
    }

    /**
     * getUidInfos
     *
     * return stdObj->uid, stdObj->displayName, stdObj->mail
     * @return stdClass
     */
    public function getUidInfos() {
        // ajout requête pour avoir mail et name sur api
        return $this->uidInfos;
    }

    /**
     * _getUidInfos
     *
     * @param  string $uid
     * @return stdClass
     */
    private function _getUidInfos($uid) : stdClass {
        $urlwsgroup = $_ENV['URLWSGROUP'];
        $infos = FBUtils::requestUidInfo($uid, $urlwsgroup);

        if (is_null($infos))
            throw new Exception("_gellFullnameWithUid erreur récupération uid: $uid");

        return $infos;
    }
}
