<?php

declare(strict_types=1);

namespace RechercheCreneaux\Type;

class Userinfo {  


    var $uid;
    var $displayName;

    var $mail;

    public function __construct($uid, $displayName, $mail) {
       $this->uid = $uid;
       $this->displayName = $displayName;
       $this->mail = $mail; 
    }
}
