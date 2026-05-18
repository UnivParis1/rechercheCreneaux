<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use Dotenv\Dotenv;
use RechercheCreneaux\FBParams;

global $relativeRoot;

$dotenv = Dotenv::createImmutable($relativeRoot);
$stdEnv = FBParams::initEnv($dotenv);

$fbParams = new FBParams($stdEnv);
