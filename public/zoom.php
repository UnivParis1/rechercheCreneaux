<?php

declare(strict_types=1);

namespace RechercheCreneaux;
use RechercheCreneaux\FBZoom;

$relativeRoot = '../';

require_once dirname(__DIR__).'/vendor/autoload.php';

session_start();

$fbParams = FBParams::factory();
$data = [];
try {
  $zoom = new FBZoom($fbParams);
  $datas = $zoom->createZoomMeeting();
} catch (\Exception $ex) {
  $datas = ['status' => false, 'msg' => $ex->getMessage()];
}

echo json_encode($datas);
