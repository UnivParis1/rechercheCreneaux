<?php

require 'paramsTest.php';

require 'vendor/autoload.php';
require 'FBUser.php';
require 'FBCompare.php';

FBUser::setDuration($dureeMinutes);

$fbUsers = array();
foreach ($users as $user) {
    $fbUser = FBUser::factory($user);
    $fbUsers[] = $fbUser;
    
//    $dataset = new Chart\Dataset([['period', $fbUser->getSequence()->length()], ['sequence', $fbUser->getSequence()]]);
//    (new Chart\GanttChart())->stroke($dataset);
}

$fbCompare = new FBCompare($fbUsers);

$period = $fbCompare->compareSequences();

if (!$period) {
    echo "pas de créneau";
    return -1;
}

die(var_dump($period));
