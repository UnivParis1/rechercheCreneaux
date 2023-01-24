<?php

require 'vendor/autoload.php';

$fd = fopen("https://echange.univ-paris1.fr/kronolith/fb.php?u=","r");

$contents = '';

while (!feof($fd)) {
    $contents .= fread($fd, 8192);
}
fclose($fd);

var_dump($contents);

?>

