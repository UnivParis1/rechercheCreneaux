<?php

declare(strict_types=1);

namespace RechercheCreneaux;
use RechercheCreneaux\FBZoom;

require 'vendor/autoload.php';

require_once("src/head.php");

$data = [];
try {
  $zoom = new FBZoom($fbParams, $stdEnv);
  $datas = $zoom->createZoomMeeting();
} catch (\Exception $ex) {
  $datas = ['status' => false, 'msg' => $ex->getMessage()];
}

echo json_encode($datas);
