<?php

namespace RechercheCreneaux;


use stdClass;
use RechercheCreneaux\FBUser;
use RechercheCreneaux\FBParams;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\FBCreneauxGeneres;

/**
 * FBForm
 */
class FBForm {
    /**
     * uids
     *
     * @var mixed
     */
    var $uids; 
    /**
     * fbUsers
     *
     * @var mixed
     */
    var $fbUsers;
    var $creneauxGenerated;

    var $fbCompare;

    var $listDate;

    public function __construct(FBParams $fbParams, stdClass $stdEnv) {
        $this->creneauxGenerated = (new FBCreneauxGeneres($fbParams, $stdEnv->dtz))->getCreneauxSeq();

        $fbUsers = array();
        foreach ($fbParams->uids as $uid) {
            $estOptionnel = false;
            if ($fbParams->listUidsOptionnels && array_search($uid, $fbParams->listUidsOptionnels) !== false) {
                $estOptionnel = true;
            }

            $fbUsers[] = FBUser::factory($uid, $stdEnv->dtz, $stdEnv->url, $fbParams->duree, $this->creneauxGenerated, $estOptionnel, $fbParams);
        }
        $this->fbUsers = $fbUsers;
        $this->fbCompare = new FBCompare($fbUsers, $this->creneauxGenerated, $stdEnv->dtz, $fbParams->nbcreneaux);
    }

    public static function validParams(FBParams $fbParams) {
        if (($fbParams->uids && sizeof($fbParams->uids) > 1)
                && ($fbParams->plagesHoraires && sizeof($fbParams->plagesHoraires) > 0)
                && $fbParams->nbcreneaux && $fbParams->duree)
                {
                    return true;
                }

        return false;
    }

    public function getCreneauxGenerated() {
        return $this->creneauxGenerated;
    }
    
    /**
     * getFbCompare
     *
     * @return FBCompare
     */
    public function getFbCompare() {
        return $this->fbCompare;
    }

    public function setFbCompare($fbCompare) {
        $this->fbCompare = $fbCompare;
    }

    public function getFbUsers() {
        return $this->fbUsers;
    }

}

?>