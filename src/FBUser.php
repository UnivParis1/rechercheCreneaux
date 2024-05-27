<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use stdClass;
use Exception;
use DateInterval;
use DateTimeZone;
use DateTime;
use League\Period\Period;
use League\Period\Duration;
use League\Period\Sequence;
use Kigkonsult\Icalcreator\Vcalendar;

/**
 * Classe regroupant la gestion des users, fait l'appel au webservice agenda et normalise les créneaux busy
 * La normalisation vise à faire correspondre les créneaux busy avec les créneaux générés dans le but de faciliter les opérations de comparaisons
 * Ex: si un créneau busy est à cheval sur 2 créneaux, remplacer le busy par ces 2 créneaux et les considéré comme busy
 */
class FBUser {

    /**
     * @var string uid
     */
    public string $uid;

    public FBParams $fbParams;

    /** @var bool $estDisqualifier
     * si son agenda n'est pas pris en compte dans les résultats
     * dans le cas où la recherche donne 0 résultats, on élimine les agendas les
     * plus chargés
    */
    public bool $estDisqualifier = false;

    /** @var bool $estFullBloquer
     * sert à déterminer si l'agenda d'une personne est bloquée
    */
    public bool $estFullBloquer = false;

    public bool $estOptionnel;

    protected Sequence $creneauxGenerated;

    /**
     * @var string url
     */
    private static String $url;

    /**
     * @var Duration duration
     */
    private static Duration $duration;

    private ?stdClass $uidInfos;

    private DateTimeZone $dateTimeZone;

    /**
     * @var string content
     */
    private String $content;

    /**
     * @var array fbusys
     */
    public Array $fbusys;
    
    /**
     * @var Sequence|null
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
    private function __construct(String $uid, String $dtz, String $url, bool $estOptionnel, FBParams $fbParams) {
        $this->fbParams = $fbParams;
        $this->uid = $uid;
        $this->setDateTimeZone($dtz);
        $this::$url = $url;
        $this->isChanged = false;
        $this->estOptionnel = $estOptionnel;

        $fd = fopen($this::$url . $uid, "r");
        $content = stream_get_contents($fd);
        fclose($fd);

        $this->content = $content;

        if ($fbParams->stdEnv->wsgroup == true) {
            $this->uidInfos = self::_getUidInfos($uid, $fbParams->stdEnv);
        }
        else {
            $this->uidInfos = null;
        }
    }

    public static function factory(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, bool $estOptionnel, FBParams $fbParams) : FBUser {
        if (!isset(self::$duration)) {
            self::setDuration($dureeEnMinutes);
        }

        $fbUser = new self($uid, $dtz, $url, $estOptionnel, $fbParams);
        $fbUser->creneauxGenerated = $creneaux;

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

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
    }

    private function _initSequence() : Sequence {
        // crée la séquence puis trie par date de début
        $sequence = FBUtils::createSequenceFromArrayFbusy($this->fbusys, $this->getDateTimeZone());
        $sequence = FBUtils::sortSequence($sequence);

        $periodBefore = Period::after(new DateTime($this->fbParams->fromDate), "{$this->fbParams->rechercheSurXJours} DAYS");

        return $sequence->filter(function (Period $interval) use ($periodBefore)  {
            if ($interval->endDate->getTimestamp() <= $periodBefore->endDate->getTimestamp()) {
                return true;
            }
            return false;
        });
    }

    private function _instanceCreneaux(Sequence &$busySeq) : Sequence {
        $creneaugenSeq = $this->getCreneauxGenerated();

        foreach ($busySeq as $busyPeriod) {

            $busyInclus = $this->_instanceCreneauxBusysInclus($creneaugenSeq, $busyPeriod, $busySeq);

            if ($busySeq->indexOf($busyPeriod) !== false) {
                $busyOverlap = $this->_instanceCreneauxBusysOverlap($creneaugenSeq, $busyPeriod, $busySeq);
            }
            else {
                $busyOverlap = false;
            }

            // Suppression des busys lorsqu'ils sont hors des periodes générées
            if ($busyInclus === false && $busyOverlap === false) {
                $busySeq = $this->_removePeriod($busyPeriod, $busySeq);
                continue;
            }
        }
        if ($this->isChanged) {
            $busySeq = FBUtils::sortSequence($busySeq);
        }
        return $busySeq;
    }

    /**
     * _instanceCreneauxBusysOverlap
     *
     * Méthode servant à tester les cas où un des créneaux généré enchevêtre une période d'un busy et réciproquement
     *
     * @param  Sequence $creneaugenSeq
     * @param  Period $busyPeriod
     * @param  Sequence $busySeq
     * @return bool
     */
    private function _instanceCreneauxBusysOverlap(Sequence $creneaugenSeq, Period $busyPeriod, Sequence &$busySeq) : bool {
        $cmpOverlapCreneau = FBUtils::_cmpSeqOverlapPeriod($creneaugenSeq, $busyPeriod);

        if ($cmpOverlapCreneau) {
            $arrayIdxGen = FBUtils::_cmpGetIdxOverlapCreneauBusy($creneaugenSeq, $busyPeriod);
            $busySeq = $this->_replaceWithArrayCreneauxGeneratedIdx($busyPeriod, $arrayIdxGen, $busySeq);
            return true;
        }
        return false;
    }

    private function _replaceWithArrayCreneauxGeneratedIdx(Period $busyPeriod, array $arrayIdxGen, Sequence &$busySeq) : Sequence {
        $creneauxGenerated = $this->getCreneauxGenerated();

        $offset = $busySeq->indexOf($busyPeriod);
        $busySeq->remove($offset);

        $indexNew = $offset;
        foreach ($arrayIdxGen as $idxCreneauxGen) {
            $newPeriod = clone ($creneauxGenerated->get($idxCreneauxGen));
            $busySeq->insert($indexNew, $newPeriod);
            $indexNew++;
        }

        return $busySeq;
    }

    /**
     * _instanceCreneauxBusysInclus
     *
     * Méthode servant à tester les cas où un des créneaux généré inclus une période d'un busy et réciproquement
     *
     * @param  Sequence $creneaugenSeq
     * @param  Period $busyPeriod
     * @return bool
     */
    private function _instanceCreneauxBusysInclus(Sequence $creneaugenSeq, Period $busyPeriod, Sequence &$busySeq) : bool {
        $cmpBusyCreneau = FBUtils::_cmpSeqContainPeriod($creneaugenSeq, $busyPeriod);

        if ($cmpBusyCreneau == 0) {
            return false;
        }

        switch ($cmpBusyCreneau) {
            case 1:
                // creneau < busy
                $busySeq = $this->_normCreneauxInferieurDuree($busyPeriod, $busySeq);
                break;
            case -1:
                // creneau > busy
                $busySeq = $this->_normCreneauxSuperieurDuree($busyPeriod, $busySeq);
                break;
            default:
                throw new Exception("Erreur comparaison creneau _normCreneaux");
        }

        return true;
    }
    private function _normCreneauxInferieurDuree(Period $periodToSplit, &$sequence) : Sequence {
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

    private function _normCreneauxSuperieurDuree(Period $period, Sequence &$busySeq) : Sequence {
        $idx = $busySeq->indexOf($period);
        $duration = $this->getDuration();
        $busySeq->remove($idx);
        $newPeriod = $period->withDurationAfterStart($duration);
        $busySeq->insert($idx, $newPeriod);
        $this->isChanged = true;
        return $busySeq;
    }

    private function _removePeriod(Period $period, Sequence &$busySeq) : Sequence {
        $idx = $busySeq->indexOf($period);
        $busySeq->remove($idx);
        $this->isChanged = true;
        return $busySeq;
    }

    private function _testSiAgendaBloque(Sequence &$busySeq) : bool {

        $testFBUserclone = clone($this);
        $seqToTest = clone($busySeq);

        // generation de créneaux standards
        $fbParamsClone = clone($this->fbParams);
        $fbParamsClone->fromDate = date('Y-m-d');
        $fbParamsClone->duree = 60;
        $fbParamsClone->plagesHoraires = array('9-12', '14-17');
        $fbParamsClone->joursDemandes = ['MO', 'TU', 'WE', 'TH', 'FR'];

        $creneauxGeneratedTest = (new FBCreneauxGeneres($fbParamsClone))->getCreneauxSeq();

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
    public function getSequence() : Sequence
    {
        return $this->sequence;
    }

    protected function setSequence(Sequence &$busySeq) {
        $this->sequence = $busySeq;
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
    public function getCreneauxGenerated() : Sequence
    {
        return $this->creneauxGenerated;
    }

    /**
     * Set the value of creneauxGenerated
     *
     * @return  \League\Period\Sequence
     */
    public function setCreneauxGenerated(&$creneauxGenerated)
    {
        $this->creneauxGenerated =& $creneauxGenerated;

        return $creneauxGenerated;
    }

    public function getEstFullBloquer() {
        return $this->estFullBloquer;
    }

    public function getEstOptionnel() {
        return $this->estOptionnel;
    }

    /**
     * récupère les informations détaillée relatives à l'utilisateur
     *
     * Return stdClass object has following structure
     * <code>
     * $uid - uid de l'utilisateur
     * $displayName - nom affiché de l'utilisateur
     * $mail - mail de l'utilisateur
     * </code>
     *
     * return stdObj->uid, stdObj->displayName, stdObj->mail
     * @return stdClass
     */
    public function getUidInfos() : stdClass {
        // ajout requête pour avoir mail et name sur api
        return $this->uidInfos;
    }

    /**
     * _getUidInfos
     *
     * @param  string $uid
     * @param  stdClass $stdEnv
     * @return stdClass
     */
    public static function _getUidInfos(string $uid, $stdEnv)  {
        $urlwsgroupUserInfos = $stdEnv->urlwsgroupUserInfos;
        $env = $stdEnv->env;
        $urlwsgroup = $urlwsgroupUserInfos . ((strtolower($env) === 'prod') ? 'Trusted':'');

        $infos = FBUtils::requestUidInfo($uid, $urlwsgroup);

        if (is_null($infos))
            throw new Exception("_gellFullnameWithUid erreur récupération uid: $uid");

        return $infos;
    }
}
