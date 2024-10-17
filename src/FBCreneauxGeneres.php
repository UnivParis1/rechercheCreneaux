<?php
declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use DatePeriod;
use RRule\RRule;
use RRule\RSet;
use DateInterval;
use DateTimeImmutable;
use RechercheCreneaux\FBUtils;
use RechercheCreneaux\FBParams;
use League\Period\Period as Period;
use League\Period\Duration as Duration;
use League\Period\Sequence as Sequence;
use JoursFeries;


/**
 * Classe servant à générer les créneaux d'après les paramètres demandés (plage horaire, jours, date de fin...)
 */
class FBCreneauxGeneres {

    private String $startDate;
    private array $days;
    private int $dureeMinutes;
    private int $rechercheSurXJours;
    private static Duration $duration;
    private Sequence $creneauxSeq;

    public function __construct(FBParams $fbParams) {
        $this->startDate = $fbParams->fromDate;
        $this->dureeMinutes = $fbParams->duree;
        $this->setDuration($fbParams->duree);
        $this->days = $fbParams->joursDemandes;
        $this->rechercheSurXJours = $fbParams->rechercheSurXJours;

        $arrPlage = $this->parsePlagesHoraires($fbParams->plagesHoraires);

        $firstCreneau = $this->getDefaultsCreneaux($this->startDate, $arrPlage[0]['h'], $arrPlage[0]['i'], $this->days, $this->rechercheSurXJours);
        $secondCreneau = $this->getDefaultsCreneaux($this->startDate, $arrPlage[2]['h'], $arrPlage[2]['i'], $this->days, $this->rechercheSurXJours);

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

    public function getDefaultsCreneaux(string $startDate, int $hours, int $minutely, array $days, int $rechercheSurXJours) {
        $dateEndCreneau = DateTime::createFromFormat('Y-m-d', $startDate)
                ->add(new DateInterval('P'. $rechercheSurXJours .'D'))
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

        $rset = new RSet();
        $rset->addRRule($arrayParams);

        $jfYStart = array_values(JoursFeries::forYear(intval(date( 'Y',strtotime($dtstart)))));
        $jfYEnd   = array_values(JoursFeries::forYear(intval(date( 'Y',strtotime($until)))));

        $joursFeriesDT =  array_unique(array_merge($jfYStart,$jfYEnd), SORT_REGULAR);

        $arraySansJoursFeries = [];
        foreach($rset as $dateGeneree) {
            $test = false;
            foreach($joursFeriesDT as $jourFerie) {
                if ($jourFerie->format('Y-m-d') == $dateGeneree->format('Y-m-d')) {
                    $test = true;
                    break;
                }
            }
            if ($test == false)
                $arraySansJoursFeries[] = $dateGeneree;
        }
        return $arraySansJoursFeries;
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
