<?php

require 'vendor/autoload.php';
require 'FBUtils.php';
require 'FBUser.php';
require 'FBCompare.php';

use RRule\RRule;

$duree = 30;
$users = array("aanli", "prigaux");
$url = "https://echange.univ-paris1.fr/kronolith/fb.php?u=";

date_default_timezone_set('Europe/Paris');
$dtz = 'Europe/Paris';

$plagesHoraires = array('9-12', '14-17');
$heuresPlage = FBUtils::parsePlagesHoraires($plagesHoraires);

$creneaux = FBUtils::getDefaultsCreneaux($duree, $heuresPlage);
$seqgen = FBUtils::createSequenceFromDT($creneaux, $duree);

$fbUsers = array();
foreach ($users as $uid) {
   $fbUser = FBUser::factory($uid, $dtz, $url, $duree, $seqgen);
    $fbUsers[] = $fbUser;
//    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
}

$fbCompare = new FBCompare($fbUsers);

$periods = $fbCompare->mergeSequences();

if (!$periods) {
    echo "pas de créneau";
    return -1;
}

$seq = FBUtils::addTimezoneToLeaguePeriods($periods, $dtz);

// fonction à optimiser
$newseq = $seqgen->subtract($seq);

die(var_dump($newseq));

//$seqgen->subtract($seq);

//FBUtils::drawSequence($periods);

//FBUtils::drawSequence($fbUsers[0]->getSequence()->jsonSerialize());

exit;
