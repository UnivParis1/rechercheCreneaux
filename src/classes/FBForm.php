<?php

namespace RechercheCreneaux;

use stdClass;
use Exception;
use League\Period\Sequence;
use RechercheCreneaux\Ressource\FBRessourceUP1;
use RechercheCreneaux\Ressource\FBRessourceGmail;
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
        foreach ($fbParams->uids as $valuid) {
            $uid = $valuid['uid'];

            switch ($valuid['type']) {
                case 'up1':
                    $estOptionnel = false;
                    if ($fbParams->listUidsOptionnels && array_search($uid, $fbParams->listUidsOptionnels) !== false) {
                        $estOptionnel = true;
                    }

                    $fbUsers[] = FBRessourceUP1::factory($uid, $stdEnv->dtz, $stdEnv->url, $fbParams->duree, $creneauxGenerated, $fbParams, $estOptionnel);
                    break;

                case 'gmail':
                    $fbUser = FBRessourceGmail::factory($uid, $stdEnv->dtz, $valuid['uri'], $fbParams->duree, $creneauxGenerated, $fbParams);

                    $fbUsers[] = $fbUser;
                    break;
                default:
                    throw new Exception("Valuid n'est pas de type up1 ou gmail");
                    break;
            }
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
            $fbInvite->sendInvite( ($this->stdEnv->env == 'prod') ? true : false);
            // Lors d'un premier appel, initialisation de jsonSessionInviteInfos
            if ($this->fbParams->jsonSessionInviteInfos == null) {
                if (!isset($_SESSION[$this->fbParams->inviteEnregistrementSessionName])) {
                    throw new Exception('Erreur session inviteEnregistrement null sur form.php');
                }
                $this->fbParams->jsonSessionInviteInfos = json_encode($_SESSION[$this->fbParams->inviteEnregistrementSessionName]);
            }
            return true;
        }
        return false;
    }

    public function getFBRessourcesDisqualifierOuBloquer() : ?array {

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
