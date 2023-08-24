<?php

use FBUtils;
use League\Period\Sequence;
use RRule\RRule;

/**
 * Description of FBGenerateCreneaux
 *
 * @author ebohm
 */
class FBGenerateCreneaux {

    private $rule;
    private $creneauxGens;
    private $duree;

    public function __construct($dtstart, $until, $duree, $hours = [9, 10, 11, 14, 15, 16], $days = ['MO', 'TU', 'WE', 'TH', 'FR'], $dtz = 'Europe/Paris') : void {
        date_default_timezone_set($dtz);
        
        $this->duree = $duree;
        
        $this->rule = new RRule([
            'FREQ' => 'MINUTELY',
            'DTSTART' => $dtstart, // '2023-07-12'
            'UNTIL' => $until, // '2023-10-23'
            'INTERVAL' => $duree,
            'BYHOUR' => $hours,
            'BYDAY' => $days
        ]);
        
        $this->creneauxGens = $this->rule->getOccurrences();        
    }
    
    public function getGeneratedSequence(): Sequence {
        
        return FBUtils::createSequenceFromDT($this->creneauxGens, $this->duree);         
    }
}
