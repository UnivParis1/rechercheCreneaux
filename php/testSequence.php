<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require 'vendor/autoload.php';

use League\Period\DatePoint;
use League\Period\Period;
use League\Period\Duration;
use League\Period\Sequence;

$periods = array();

$periods[] = Period::fromDay(2018,01,01);
$periods[] = Period::fromDay(2017,01,01);
$periods[] = Period::fromDay(2018,01,01);

$sequence = new Sequence();

foreach($periods as $period) {
    $sequence->push($period);
}
$intersections = $sequence->intersections(); // a new Sequence object
die(var_dump($intersections->isEmpty()));

