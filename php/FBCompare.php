<?php

use League\Period\DatePoint;
use League\Period\Period;
use League\Period\Duration;
use League\Period\Sequence;


/**
 * Description of FBCompare
 *
 * @author ebohm
 */
class FBCompare {
        
    private Array $arrayFBUsers;
        
    public function __construct(&$arrayFBUsers) {        
        $this->arrayFBUsers = $arrayFBUsers;
    }

public function compareSequences() {
        
        $sequenceResult = new Sequence();        

        $sequenceRef = $this->arrayFBUsers[0]->getSequence();
        $periodsRef = $sequenceRef->jsonSerialize();

        foreach ($periodsRef as $period) {            
            foreach ($this->arrayFBUsers as $idx => $FBUser) {
               if ($idx === array_key_first($this->arrayFBUsers))
                   continue;
               
               $seqs = $FBUser->getSequence();
               if ($this->comparePeriodExistRecursif($period, 1, $this->arrayFBUsers)) {
//                   if ($sequenceResult->contains($period) == False) {
//                       $sequenceResult->push($period);
//                   }
                   return $period;
               }
            }
        }
        
        return false;
    }
    
    private function comparePeriodExistRecursif(Period $periodRef, $idx, $FBUsers) {
        $lastIdx = array_key_last($FBUsers);
        
        $seq = $FBUsers[$idx]->getSequence();
        
        foreach ($seq->jsonSerialize() as $period) {
            if ($period->equals($periodRef)) {
                if ($lastIdx==$idx) {
                    return True;
                }
                $this->comparePeriodExistRecursif($periodRef, $idx++, $FBUsers);
            }
        }
        return False;
    }
    
//    public function compareSequencesPbAlgo() {
//        
//        $sequenceResult = new Sequence();
//
//        $idxUsr = 0;
//        foreach ($this->arrayFBUsers as $FBUsersCmp1) {
//            
//            $sequenceCmp1 = $FBUsersCmp1->getSequence();
//            
//            $idx2Usr = 0;
//            foreach ($this->arrayFBUsers as $FBUsersCmp2) {
//                
//                $sequenceCmp2 = $FBUsersCmp2->getSequence();
//                
//                if ($sequenceCmp1 == $sequenceCmp2) {
//                    $idx2Usr++;
//                    continue;
//                }
//
//                $periods = $sequenceCmp2->jsonSerialize();
//                $testB = False;
//                foreach ($periods as $period) {
//                    if ($sequenceCmp1->contains($period)) {
//                        if ($sequenceResult->contains($period)) {
//                            break;
//                        }
//                        $testB = True;
//                        $periodToInsert = $period;
//                    }
//                }
//                $idx2Usr++;
//            }
//
//            if ($testB && !$sequenceResult->contains($periodToInsert)) {
//                $sequenceResult->push($periodToInsert);
//            }
//            $idxUsr++;
//        }
//
//        return $sequenceResult;
//    }
}
