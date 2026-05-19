<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use Exception;
use stdClass;
use RechercheCreneaux\FBZoom;

$relativeRoot = '../';

require_once dirname(__DIR__).'/vendor/autoload.php';

session_start();

require_once dirname(__DIR__)."/src/includes/head_inc.php";

if (!$stdEnv->agendasDistantsUploadPath)
    throw new Exception("agendasDistantsUploadPath n'existe pas dans .env");

$datas = ['status' => false];

$temp = explode(".", $_FILES["file"]["name"]);
$extension = end($temp);

if (count($_FILES)==1 && $_FILES["file"]["type"] == "text/calendar" && $_FILES["file"]["size"] < 2000000 && $extension == 'ics')
    if ($_FILES["file"]["error"] > 0)
        $datas['msg'] = "Return Code: " . $_FILES["file"]["error"];
    else
        $datas = wfile($_FILES, $stdEnv);
else
    $datas['msg'] = "Invalid file";

function wfile(array $files,stdClass $stdEnv): array {
        $msg = "success";
        $name = $files["file"]['full_path'];
        $tmp_name = $files["file"]["tmp_name"];
        $status = true;

        $content = file_get_contents($tmp_name);

        $temp = explode('/', $tmp_name);
        $nametmp = end($temp);

        $fullname = $stdEnv->agendasDistantsUploadPath . $nametmp;
        file_put_contents($fullname, $content);

        return ['status' => $status, 'name' => $name, 'fullname' => $fullname, 'msg' => $msg];
}

echo json_encode($datas);
