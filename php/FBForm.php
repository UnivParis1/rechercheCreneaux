<?php

class FBForm {

    var $uids;
    var $fbUsers;
    var $creneauxGenerated;

    var $fbCompare;

    var $listDate;

    public function __construct(stdClass $stdParams, stdClass $stdEnv) {
        $this->creneauxGenerated = (new FBCreneauxGeneres($stdParams, $stdEnv->dtz))->getCreneauxSeq();

        $fbUsers = array();
        foreach ($stdParams->uids as $uid) {
            $estOptionnel = false;
            if ($stdParams->listUidsOptionnels && array_search($uid, $stdParams->listUidsOptionnels) !== false) {
                $estOptionnel = true;
            }

            $fbUsers[] = FBUser::factory($uid, $stdEnv->dtz, $stdEnv->url, $stdParams->duree, $this->creneauxGenerated, $estOptionnel);
        }
        $this->fbUsers = $fbUsers;
        $this->fbCompare = new FBCompare($fbUsers, $this->creneauxGenerated, $stdEnv->dtz, $stdParams->nbcreneaux);
    }

    public static function validParams(stdClass $stdParams) {
        if (($stdParams->uids && sizeof($stdParams->uids) > 1) && ($stdParams->plagesHoraires && sizeof($stdParams->plagesHoraires) > 0) && $stdParams->nbcreneaux && $stdParams->duree)
            return true;

        return false;
    }

    public function getCreneauxGenerated() {
        return $this->creneauxGenerated;
    }

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