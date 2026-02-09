<?php

declare(strict_types=1);

namespace RechercheCreneaux;
use RechercheCreneaux\FBZoom;

$relativeRoot = '../';

require_once dirname(__DIR__).'/vendor/autoload.php';

session_start();

require_once dirname(__DIR__)."/src/includes/head_inc.php";

$data = [];
try {
  $zoom = new FBZoom($fbParams, $stdEnv);
  $datas = $zoom->createZoomMeeting();
} catch (\Exception $ex) {
  $datas = ['status' => false, 'msg' => $ex->getMessage()];
}

echo json_encode($datas);
