<?php

declare(strict_types=1);

$absPath = dirname(__DIR__);
$relativeRoot = '../';

require_once $absPath . '/vendor/autoload.php';

session_start();

require_once $absPath . "/src/includes/main_inc.php";
