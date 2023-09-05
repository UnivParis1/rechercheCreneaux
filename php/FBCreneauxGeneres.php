<?php

//use FBUtils;

/**
 * Description of FBCreneauxGeneres
 *
 * @author ebohm
 */
class FBCreneauxGeneres {

    private string $dtz;
    private array $plagesHoraires;
    private int $dureeMinutes;
    private League\Period\Sequence $creneauxSeq;

    public function __construct(int $dureeMinutes, array $plagesHoraires, string $dtz) {
        $this->dureeMinutes = $dureeMinutes;
        $this->plagesHoraires = $plagesHoraires;
        $this->dtz = $dtz;

        $heuresPlage = FBUtils::parsePlagesHoraires($plagesHoraires);

        $creneaux = FBUtils::getDefaultsCreneaux($dureeMinutes, $heuresPlage);

        $this->creneauxSeq = FBUtils::createSequenceFromDT($creneaux, $dureeMinutes);
    }
    
    public function getCreneauxSeq() {
        return $this->creneauxSeq;
    }
}
