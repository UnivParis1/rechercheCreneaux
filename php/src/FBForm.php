<?php

namespace RechercheCreneaux;

use stdClass;
use Exception;
use League\Period\Sequence;
use RechercheCreneaux\FBUser;
use RechercheCreneaux\FBParams;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\FBCreneauxGeneres;

/**
 * Classe centrale de l'application, référençant les autres objets et paramètres
 */
class FBForm {

    private array $fbUsers;
    private Sequence $creneauxGenerated;

    public FBCompare $fbCompare;
    public FBParams $fbParams;

    private stdClass $stdEnv;

    /**
     * @param FBParams $fbParams
     * @param stdClass $stdEnv
     */
    public function __construct(FBParams $fbParams, stdClass $stdEnv) {
        $this->fbParams = $fbParams;
        $this->stdEnv = $stdEnv;

        $creneauxGenerated = (new FBCreneauxGeneres($fbParams))->getCreneauxSeq();

        $fbUsers = array();
        foreach ($fbParams->uids as $uid) {
            $estOptionnel = false;
            if ($fbParams->listUidsOptionnels && array_search($uid, $fbParams->listUidsOptionnels) !== false) {
                $estOptionnel = true;
            }

            $fbUsers[] = FBUser::factory($uid, $stdEnv->dtz, $stdEnv->url, $fbParams->duree, $creneauxGenerated, $estOptionnel, $fbParams);
        }
        $this->fbUsers = $fbUsers;
        $this->creneauxGenerated = $creneauxGenerated;
        $this->fbCompare = new FBCompare($fbUsers, $this->creneauxGenerated, $stdEnv->dtz, $fbParams->nbcreneaux);
    }

    /**
     * Valide les paramètres
     *
     * @param FBParams $fbParams
     *
     * @return bool
     */
    public static function validParams(FBParams $fbParams) : bool {
        if (($fbParams->uids && sizeof($fbParams->uids) > 1)
                && ($fbParams->plagesHoraires && sizeof($fbParams->plagesHoraires) > 0)
                && $fbParams->nbcreneaux && $fbParams->duree)
                {
                    return true;
                }

        return false;
    }

    public function invitationProcess(array $listDate) : bool {
        if (FBInvite::verifSiInvitation($this->fbParams)) {
            $fbInvite = new FBInvite($this, $this->fbParams, $this->stdEnv, $listDate);
            $fbInvite->sendInvite();
            // Lors d'un premier appel, initialisation de jsonSessionInfos
            if ($this->fbParams->jsonSessionInfos == null) {
                if (!isset($_SESSION['inviteEnregistrement'])) {
                    throw new Exception('Erreur session inviteEnregistrement null sur form.php');
                }
                $this->fbParams->jsonSessionInfos = json_encode($_SESSION['inviteEnregistrement']);
            }
            return true;
        }
        return false;
    }

    public function getFBUsersDisqualifierOrBloquer() : ?array {

        $fbUsers = array();
        foreach ($this->fbUsers as $fbUser) {
            if ($fbUser->estDisqualifier || $fbUser->estFullBloquer) {
                $fbUsers[] = $fbUser;
            }
        }

        if (count($fbUsers) > 0) {
            return $fbUsers;
        }
        else {
            return null;
        }
    }

    public function getCreneauxGenerated() : Sequence {
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