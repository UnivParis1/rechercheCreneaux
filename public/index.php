<?php

declare(strict_types=1);

$absPath = dirname(__DIR__);
$relativeRoot = '../';

require_once $absPath . '/vendor/autoload.php';

session_start();

require_once $absPath . "/src/includes/head_inc.php";

require_once $absPath . "/src/includes/index_inc.php";
