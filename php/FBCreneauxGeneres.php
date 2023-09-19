<?php

//use FBUtils;
use League\Period\Sequence as Sequence;
use League\Period\Period as Period;

/**
 * Description of FBCreneauxGeneres
 *
 * @author ebohm
 */
class FBCreneauxGeneres {

    private string $dtz;
    private array $plagesHoraires;
    private int $dureeMinutes;
    private ?League\Period\Sequence $creneauxSeq;

    public function __construct(int $dureeMinutes, array $plagesHoraires, string $dtz) {
        $this->dureeMinutes = $dureeMinutes;
        $this->plagesHoraires = $plagesHoraires;
        $this->dtz = $dtz;

        $heuresPlage = FBUtils::parsePlagesHoraires($plagesHoraires);

        $creneaux = FBUtils::getDefaultsCreneaux($dureeMinutes, $heuresPlage);

        $creneauxSeq = FBUtils::createSequenceFromDT($creneaux, $dureeMinutes);

        // Necessaire si la plage demandée n'est pas spécifique aux heures pleines (ex: 9H30-11H, 9H-11H30 etc...)
        $this->creneauxSeq = $this->_filtrerDemieHeure($creneauxSeq);
    }

    /**
     * getCreneauxSeq
     *
     * @return Sequence
     */
    public function getCreneauxSeq() : Sequence {
        return $this->creneauxSeq;
    }

    /**
     * _filterDemieHeure
     *
     * Cette méthode a pour but d'enlever les creneaux générés avant les demie heure:
     * Exemple: pour une plage 9h30-14. La génération des créneaux se fait à partir de 9H. Donc on enleve les créneaux générés entre 9H et 9H30
     *
     * @param  Sequence $creneauxSeq
     * @return Sequence
     */
    private function _filtrerDemieHeure(Sequence $creneauxSeq) : Sequence {
        $testHI = array(array('h'=>9, 'i'=>30), array('h' => 12, 'i'=>30), array('h' => 13, 'i' => 30), array('h'=>17, 'i'=>30));

        $testHI = $this->_getArrayPlages();

        $newSeq = $creneauxSeq->filter(function (Period $interval) use ($testHI) : bool {
            $startDate = $interval->startDate;
            $endDate = $interval->endDate;

            $debut = clone $startDate;
            $debut = $debut->setTime($testHI[0]['h'], $testHI[0]['i']);

            $fin = $interval->endDate;
            $fin = $fin->setTime($testHI[3]['h'], $testHI[3]['i']);

            $milieudebut = clone $startDate;
            $milieudebut = $milieudebut->setTime($testHI[1]['h'], $testHI[1]['i']);

            $milieufin = clone $startDate;
            $milieufin = $milieufin->setTime($testHI[2]['h'], $testHI[2]['i']);

            $fin = $interval->endDate;
            $fin = $fin->setTime($testHI[3]['h'], $testHI[3]['i']);

            if ($startDate < $debut)
                return false;
            if ($endDate > $fin)
                return false;

            if ($startDate >= $milieudebut && $endDate <= $milieufin)
                return false;

            return true;
        });

        return $newSeq;
    }

    private function _getArrayPlages() {
        $plagesHoraires = $this->plagesHoraires;
        $array = array();
        foreach ($plagesHoraires as $plages) {
            $arrayPlages = explode('-', $plages);
            foreach ($arrayPlages as $plage) {
                $arrayHeures = explode('H', $plage);
                if (sizeof($arrayHeures) > 1)
                    $minute = (int) $arrayHeures[1];
                else
                    $minute = 0;
                $array[] = array('h' => (int) $arrayHeures[0], 'i' => $minute);
            }
        }
        return $array;
    }
}
