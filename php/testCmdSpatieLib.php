<?php

require 'paramsTest.php';

require 'vendor/autoload.php';
require 'FBUtils.php';
require 'FBUser.php';
require 'FBCompare.php';

use RRule\RRule;

date_default_timezone_set('Europe/Paris');
$dtz = new DateTimeZone("Europe/Paris");

//xdebug_break();
// composer rlanvin/php-rrule
$r = new RRule([
    'FREQ' => 'MINUTELY',
    'DTSTART' => '2023-07-10',
//    'TZID' => 'Europe/Brussels',
    'UNTIL' => '2023-10-23',
    'INTERVAL' => 30,
    'BYHOUR' => [9, 10, 11, 14, 15, 16],
    'BYDAY' => ['MO','TU', 'WE','TH', 'FR']
]);

$creneaux = $r->getOccurrences();

$seqgen = new \League\Period\Sequence();

foreach ($creneaux as $creneau) {
    $dateend = DateTimeImmutable::createFromMutable($creneau);
    $end = $dateend->add(new DateInterval('PT30M'));
    $period = \League\Period\Period::fromDate($creneau, $end);
    
    $seqgen->push($period);
}

//$seqgen = $seqgen->unions();

//die(var_dump($seqgen));

//die(var_dump($r->occurrences));

FBUser::setDuration($dureeMinutes);
FBUser::setUrl($url);

$fbUsers = array();
foreach ($users as $user) {
   $fbUser = FBUser::factory($user);
    $fbUsers[] = $fbUser;
//    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
}

$fbCompare = new FBCompare($fbUsers);

$periods = $fbCompare->mergeSequences();

xdebug_break();

function setTimezoneToArrayLeaguePeriods($periods, $dtz) {
    $seq = new \League\Period\Sequence();
//die(var_dump($periods));
    foreach ($periods as $period) {
        $dstart = DateTime::createFromImmutable($period->startDate);
        $dstart->setTimezone($dtz);
        $dend = DateTime::createFromImmutable($period->endDate);
        $dend->setTimezone($dtz);

        $newPeriod = \League\Period\Period::fromDate($dstart, $dend);
        $seq->push($newPeriod);
//    echo $period->startDate->format('Y-m-d H:i') . ';' . $period->endDate->format('Y-m-d H:i') . "\n";
    }
    
    return $seq;
}

use Carbon\CarbonPeriod;
use Cmixin\EnhancedPeriod;

function periods_leagueToPeriod_spatie($arrayP) {

    $periodCollection = new Spatie\Period\PeriodCollection();

    foreach ($arrayP as $p) {
        $start = $p->startDate;
        $end = $p->endDate;

        $np = Spatie\Period\Period::make($start, $end, Spatie\Period\Precision::MINUTE());

        $periodCollection = $periodCollection->add($np);
    }

    return $periodCollection;
}

if (!$periods) {
    echo "pas de créneau";
    return -1;
}

$seq = setTimezoneToArrayLeaguePeriods($periods, $dtz);

//$periods = periods_leagueToPeriod_spatie($seq->toList());
//$periodsGen = periods_leagueToPeriod_spatie($seqgen->toList());
//$newperiod = $periodsGen->subtract($periods);


$newseq = $seqgen->subtract($seq);

die(var_dump($newseq));

//$seqgen->subtract($seq);

//FBUtils::drawSequence($periods);

//FBUtils::drawSequence($fbUsers[0]->getSequence()->jsonSerialize());

exit;
