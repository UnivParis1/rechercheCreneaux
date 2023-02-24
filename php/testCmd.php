<?php

require 'paramsTest.php';

require 'vendor/autoload.php';
require 'FBUtils.php';
require 'FBUser.php';
require 'FBCompare.php';

FBUser::setDuration($dureeMinutes);
FBUser::setUrl($url);

$fbUsers = array();
foreach ($users as $user) {
    $fbUser = FBUser::factory($user);
    $fbUsers[] = $fbUser;
    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
}

$fbCompare = new FBCompare($fbUsers);

$periods = $fbCompare->compareSequences();

if (!$periods) {
    echo "pas de créneau";
    return -1;
}

FBUtils::drawSequence($periods);

FBUtils::drawSequence($fbUsers[0]->getSequence()->jsonSerialize());

exit;
