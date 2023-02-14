<?php

use League\Period\Period;
use League\Period\Sequence;
use League\Period\DatePoint;
use League\Period\Chart;


/**
 * Description of FBUtils
 *
 * @author ebohm
 */
class FBUtils {

    public static function drawSequence(array $periods) {
        
        $sequence = FBUtils::createSequenceFromArrayPeriods($periods);
        $sequence = FBUtils::sortSequence($sequence);

        $dataset = new Chart\Dataset([['period', $sequence->length()], ['sequence', $sequence]]);
        (new Chart\GanttChart())->stroke($dataset);
    }
    
    public static function sortSequence(Sequence $sequence) {
        $sequence->sort(function (Period $period1, Period $period2): int {
            if ($period1->startDate == $period2->startDate && $period1->endDate == $period2->endDate) {
                $this->sequence->remove($period2);
                return 0;
            }
            return $period1->startDate <=> $period2->startDate;
        });

        return $sequence;
    }
    
    public static function createSequenceFromArrayFbusy(array $fbusys) {
        
        $sequence = new Sequence();
        
        foreach ($fbusys as $fbusy) {
            
            $fbusdate = $fbusy[0];

            $t1DateTime = $fbusdate[0];
            $t2DateTime = $fbusdate[1];

            $t1DatePoint = DatePoint::fromDate($t1DateTime);
            $t2DatePoint = DatePoint::fromDate($t2DateTime);

            $period = Period::fromDate($t1DatePoint, $t2DatePoint);
            
            $sequence->push($period);
        }

        return $sequence;
    }
    
    public static function createSequenceFromArrayPeriods(array $periods) {
        $sequence = new Sequence();
        
        foreach ($periods as $period) {
            $sequence->push($period);
        }
        
        return $sequence;
    }
    
//    public static function createCSV($name, array $periods) {
//        $writer = Writer::createFromPath("./$name.csv", 'w+');
//        
//        $writer->insertAll($periods);        
//    }
}
