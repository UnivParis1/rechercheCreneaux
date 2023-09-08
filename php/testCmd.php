<?php

require 'vendor/autoload.php';
require 'FBUtils.php';
require 'FBUser.php';
require 'FBCompare.php';
require 'FBCreneauxGeneres.php';

use RRule\RRule;

$duree = 12;
$users = array("aanli", "gurret");
$nbcreneaux = 10;
$url = "https://echange.univ-paris1.fr/kronolith/fb.php?u=";

date_default_timezone_set('Europe/Paris');
$dtz = 'Europe/Paris';

$plagesHoraires = array('9-12', '14-17');
$creneauxGenerated = (new FBCreneauxGeneres($duree, $plagesHoraires, $dtz))->getCreneauxSeq();

$fbUsers = array();
foreach ($users as $uid) {
    $fbUsers[] = FBUser::factory($uid, $dtz, $url, $duree, $creneauxGenerated);
//    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
}

$fbCompare = new FBCompare($fbUsers, $creneauxGenerated, $dtz, $nbcreneaux);
$creneauxFinauxArray = $fbCompare->getArrayCreneauxAffiches();

if (count($creneauxFinauxArray) == 0) {
    echo "pas de creneaux trouvés";
    return -1;
}

echo '<pre>';
die(var_export($creneauxFinauxArray));

//$seqgen->subtract($seq);

//FBUtils::drawSequence($periods);

//FBUtils::drawSequence($fbUsers[0]->getSequence()->jsonSerialize());

exit;
