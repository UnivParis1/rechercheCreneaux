<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateInterval;
use DateTime;
use DateTimeZone;
use Kigkonsult\Icalcreator\Vcalendar;
use League\Period\Duration;
use League\Period\Period;
use League\Period\Sequence;
use RechercheCreneaux\FBParams;
use RechercheCreneaux\Type\Userinfo;

class FBRessource
{
    public ?Userinfo $uidInfos;

    public FBParams $fbParams;

    /**
     * @var string uid
     */
    public string $uid;

    /**
     * @var string url
     */
    public String $url;

    /**
     * @var string content
     */
    protected ?String $content;

    public bool $estOptionnel;

    /**
     * @var Duration duration
     */
    protected static Duration $duration;

    protected DateTimeZone $dateTimeZone;

    protected Sequence $creneauxGenerated;
    /**
     *
     * @var array fbusys
     */
    public Array $fbusys;
    
    /**
     * @var Sequence|null
     */
    protected ?Sequence $sequence;

    protected bool $isChanged;

    /** @var bool $estFullBloquer
     * sert à déterminer si l'agenda d'une personne est bloquée
    */
    public bool $estFullBloquer = false;

   /** @var bool $estDisqualifier
     * si son agenda n'est pas pris en compte dans les résultats
     * dans le cas où la recherche donne 0 résultats, on élimine les agendas les
     * plus chargés
    */
    public bool $estDisqualifier = false;

    public bool $httpError = true;

    public bool $valid = false;

    public function __construct(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams, bool $estOptionnel)
    {
        if (!isset(self::$duration)) {
            self::setDuration($dureeEnMinutes);
        }

        $this->fbParams = $fbParams;

        $this->estOptionnel = $estOptionnel;

        $this->uid = $uid;
        $this->isChanged = false;
        $this->url = $url;
        $this->creneauxGenerated = $creneaux;
        $this->setDateTimeZone($dtz);

        $curl_handle=curl_init();

        curl_setopt($curl_handle, CURLOPT_URL, $this->url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Université Paris 1 Pantheon-Sorbonne rechercheCreneaux');

        $content = curl_exec($curl_handle);

        $httpcode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        curl_close($curl_handle);

        $this->httpError = $httpcode == 200 ? false : true;

        $this->content = $this->httpError ? null: $content;
    }

    public function getDisplayName(): string {
        return $this->uid;
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

    public function getDateTimeZone(): DateTimeZone
    {
        return $this->dateTimeZone;
    }

    public function setDateTimeZone(String $dtz): void
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
    public function setCreneauxGenerated(&$creneauxGenerated): \League\Period\Sequence
    {
        $this->creneauxGenerated =& $creneauxGenerated;

        return $creneauxGenerated;
    }

    public function getEstOptionnel(): bool {
        return $this->estOptionnel;
    }

    protected function _initSequence() : Sequence {
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

    protected function _instanceCreneaux(Sequence &$busySeq) : Sequence {
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


    public function getEstFullBloquer() {
        return $this->estFullBloquer;
    }

    protected function _testSiAgendaBloque(Sequence &$busySeq) : bool {

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
     * Get the value of uidInfos
     */
    public function getUidInfos()
    {
        return $this->uidInfos;
    }

    /**
     * Set the value of uidInfos
     *
     * @return  self
     */
    public function setUidInfos($uidInfos)
    {
        $this->uidInfos = $uidInfos;

        return $this;
    }
}
