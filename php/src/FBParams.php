<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;

class FBParams {

    var $varsHTTPGet;

    var $actionFormulaireValider;

    var $uids;

    var $nbcreneaux;

    var $duree;

    var $plagesHoraires;

    var $joursDemandes;

    var $fromDate;

    var $titleEvent;

    var $descriptionEvent;

    var $lieuEvent;

    var $modalCreneauStart;

    var $modalCreneauEnd;

    var $listUidsOptionnels;

    var $jsonSessionInfos;

    public function __construct(stdClass $stdEnv) {

        $this->actionFormulaireValider = isset($stdEnv->varsHTTPGet['actionFormulaireValider']) ? $stdEnv->varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
        $this->uids = isset($stdEnv->varsHTTPGet['listuids']) ? $stdEnv->varsHTTPGet['listuids'] : null;
        $this->nbcreneaux = isset($stdEnv->varsHTTPGet['creneaux']) ? (int) $stdEnv->varsHTTPGet['creneaux'] : null;
        $this->duree = isset($stdEnv->varsHTTPGet['duree']) ? (int) $stdEnv->varsHTTPGet['duree'] : null;
        $this->plagesHoraires = isset($stdEnv->varsHTTPGet['plagesHoraires']) ? $stdEnv->varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
        $this->joursDemandes = isset($stdEnv->varsHTTPGet['joursCreneaux']) ? $stdEnv->varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
        $this->fromDate = isset($stdEnv->varsHTTPGet['fromDate']) ? $stdEnv->varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
        $this->titleEvent = isset($stdEnv->varsHTTPGet['titrecreneau']) ? $stdEnv->varsHTTPGet['titrecreneau'] : null;
        $this->descriptionEvent = isset($stdEnv->varsHTTPGet['summarycreneau']) ? $stdEnv->varsHTTPGet['summarycreneau'] : null;
        $this->lieuEvent = isset($stdEnv->varsHTTPGet['lieucreneau']) ? $stdEnv->varsHTTPGet['lieucreneau'] : null;
        $this->modalCreneauStart = isset($stdEnv->varsHTTPGet['modalCreneauStart']) ? $stdEnv->varsHTTPGet['modalCreneauStart'] : null;
        $this->modalCreneauEnd = isset($stdEnv->varsHTTPGet['modalCreneauEnd']) ? $stdEnv->varsHTTPGet['modalCreneauEnd'] : null;
        $this->listUidsOptionnels = isset($stdEnv->varsHTTPGet['listUidsOptionnels']) ? $stdEnv->varsHTTPGet['listUidsOptionnels'] : null;
        $this->jsonSessionInfos = isset($_SESSION['inviteEnregistrement']) ? json_encode($_SESSION['inviteEnregistrement']) : null;
        
        if ((new DateTime($this->fromDate)) < (new DateTime())) {
            $this->fromDate = (new DateTime())->format('Y-m-d');
        }
    }

}

?>