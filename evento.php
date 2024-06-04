<?php

declare(strict_types=1);

namespace RechercheCreneaux;
use RechercheCreneaux\FBEvento;

require 'vendor/autoload.php';

require_once("head.php");

$data = [];
try {
  $zoom = new FBEvento();
} catch (\Exception $ex) {
  $datas = ['status' => false, 'msg' => $ex->getMessage()];
}

echo json_encode($datas);
