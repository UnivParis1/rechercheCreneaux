<?php
declare(strict_types=1);

//use FBUtils;
use League\Period\Sequence as Sequence;
use League\Period\Period as Period;
use League\Period\Duration as Duration;
use League\Period\Bounds as Bounds;
use RRule\RRule;

/**
 * Description of FBCreneauxGeneres
 *
 * @author ebohm
 */
class FBCreneauxGeneres {

    private String $startDate;
    private String $dtz;
    private array $plagesHoraires;
    private array $days;
    private int $dureeMinutes;
    private static Duration $duration;
    private League\Period\Sequence $creneauxSeq;

    public function __construct(stdClass $stdParams, $dtz) {
        $this->startDate = $stdParams->fromDate;
        $this->dureeMinutes = $stdParams->duree;
        $this->setDuration($stdParams->duree);
        $this->plagesHoraires = $stdParams->plagesHoraires;
        $this->days = $stdParams->joursDemandes;
        $this->dtz = $dtz;

        $arrPlage = $this->parsePlagesHoraires($stdParams->plagesHoraires);

        $firstCreneau = $this->getDefaultsCreneaux($this->startDate, $this->dureeMinutes, $arrPlage[0]['h'], $arrPlage[0]['i'], $this->days);
        $secondCreneau = $this->getDefaultsCreneaux($this->startDate, $this->dureeMinutes, $arrPlage[2]['h'], $arrPlage[2]['i'], $this->days);

        $this->creneauxSeq = new Sequence();
        $this->generateSequence($firstCreneau, $this->dureeMinutes, $arrPlage[0], $arrPlage[1]);
        $this->generateSequence($secondCreneau, $this->dureeMinutes, $arrPlage[2], $arrPlage[3]);

        $this->creneauxSeq = FBUtils::sortSequence($this->creneauxSeq);
    }

    /**
     * getCreneauxSeq
     *
     * @return Sequence
     */
    public function getCreneauxSeq() : Sequence {
        return $this->creneauxSeq;
    }

    public function getDefaultsCreneaux($startDate, $dureeEnMinutes, int $hours, int $minutely, array $days, int $addXmonth = 1) {
        $dateEndCreneau = DateTime::createFromFormat('Y-m-d', $startDate)
                ->add(new DateInterval('P'. $addXmonth .'M'))
                ->format('Y-m-d');

        return self::generateCreneaux($startDate, $dateEndCreneau, array($hours), $days, array($minutely));
    }

    private function generateCreneaux($dtstart, $until, $hours, $days, $minutely = [0], $interval = 1) {
        $arrayParams = [
            'FREQ' => 'DAILY',
            'DTSTART' => $dtstart, // '2023-07-12'
            'UNTIL' => $until, // '2023-10-23'
            'INTERVAL' => $interval,
            'BYHOUR' => $hours,
            'BYMINUTE' => $minutely,
            'BYDAY' => $days];

        $r = new RRule($arrayParams);

        return $r->getOccurrences();
    }

    private function generateSequence(array $creneaux, $dureeMinutes, array $minTime, array $maxTime) {

        foreach ($creneaux as $creneau) {
            $dtCreneau = DateTimeImmutable::createFromMutable($creneau);
            $dateEndMax =  clone $dtCreneau;
            $dateEndMax = $dateEndMax->setTime($maxTime['h'], $maxTime['i']);

            $dateMinMax =  clone $dtCreneau;
            $dateMinMax = $dateEndMax->setTime($minTime['h'], $minTime['i']);

            $interval = Period::fromDateRange(new DatePeriod(
                $dateMinMax,
                new DateInterval('PT'. $dureeMinutes.'M'),
                $dateEndMax));

            foreach ($interval->dateRangeForward($this->getDuration()) as $newCreneau) {
                $newCreneauEnd = $newCreneau->add($this->getDuration()->dateInterval);
                if ($newCreneauEnd > $dateEndMax)
                    continue;
                $this->creneauxSeq->push(Period::fromDate($newCreneau, $newCreneau->add($this->getDuration()->dateInterval)));
            }
        }
    }

    public function parsePlagesHoraires(array $plagesHoraires) {
        $arrHours = array();
        foreach ($plagesHoraires as $plages) {
            $heures = explode('-', $plages);
            foreach ($heures as $heure) {
                $hs = explode('H', $heure);
                if (count($hs) > 1) {
                    $arrHours[] = ['h' => (int)$hs[0], 'i' => (int) $hs[1]];
                }
                else {
                    $arrHours[] = ['h' => (int)$hs[0], 'i' => 0];
                }
            }
        }
        return $arrHours;
    }

    /**
     * Get the value of duration
     */
    public function getDuration()
    {
        return self::$duration;
    }

    /**
     * Set the value of duration
     *
     * @return  self
     */
    public function setDuration($dureeMinutes)
    {
        $dateInterval = new DateInterval("PT".$dureeMinutes."M");
        self::$duration = Duration::fromDateInterval($dateInterval);
    }
}
