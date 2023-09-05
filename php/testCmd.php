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
$creneauxGenerated = FBUtils::createSequenceFromDT($creneaux, $duree);

$fbUsers = array();
foreach ($users as $uid) {
    $fbUsers[] = FBUser::factory($uid, $dtz, $url, $duree, $creneauxGenerated);
//    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
}

$fbCompare = new FBCompare($fbUsers, $creneauxGenerated);
$creneauxFinaux = $fbCompare->substractBusysFromCreneaux();

if ($creneauxFinaux->length() === null) {
    echo "pas de creneaux trouvés";
    return -1;
}

echo '<pre>';
die(var_export($creneauxFinaux));

//$seqgen->subtract($seq);

//FBUtils::drawSequence($periods);

//FBUtils::drawSequence($fbUsers[0]->getSequence()->jsonSerialize());

exit;
