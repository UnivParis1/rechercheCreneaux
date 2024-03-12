<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;

/**
 * Classe regroupant les paramètres globaux
 * Issu d'une stdclass, a été formalisée comme classe pour plus de clarté
 */
class FBParams {

    var array $varsHTTPGet;

    var $actionFormulaireValider;

    var ?array $uids;

    var ?int $nbcreneaux;

    var ?int $duree;

    var array $plagesHoraires;

    var array $joursDemandes;

    var int $rechercheSurXJours;

    var string $fromDate;

    var ?string $titleEvent;

    var ?string $descriptionEvent;

    var ?string $lieuEvent;

    var ?string $modalCreneauStart;

    var ?string $modalCreneauEnd;

    var ?array $listUidsOptionnels;

    var ?string $jsonSessionInfos;

    public stdClass $stdEnv;

    public function __construct(stdClass $stdEnv) {

        $this->stdEnv = $stdEnv;
        $this->actionFormulaireValider = isset($stdEnv->varsHTTPGet['actionFormulaireValider']) ? $stdEnv->varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
        $this->uids = isset($stdEnv->varsHTTPGet['listuids']) ? $stdEnv->varsHTTPGet['listuids'] : null;
        $this->nbcreneaux = isset($stdEnv->varsHTTPGet['creneaux']) ? (int) $stdEnv->varsHTTPGet['creneaux'] : null;
        $this->duree = isset($stdEnv->varsHTTPGet['duree']) ? (int) $stdEnv->varsHTTPGet['duree'] : null;
        $this->plagesHoraires = isset($stdEnv->varsHTTPGet['plagesHoraires']) ? $stdEnv->varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
        $this->joursDemandes = isset($stdEnv->varsHTTPGet['joursCreneaux']) ? $stdEnv->varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
        $this->fromDate = isset($stdEnv->varsHTTPGet['fromDate']) ? $stdEnv->varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
        $this->rechercheSurXJours = isset($stdEnv->varsHTTPGet['rechercheSurXJours']) ? intval($stdEnv->varsHTTPGet['rechercheSurXJours']) : $stdEnv->rechercheSurXJours;
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