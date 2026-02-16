<?php


declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use DateInterval;
use IntlDateFormatter;
use Exception;
use RechercheCreneaux\FBForm;
use RechercheCreneaux\FBUtils;
use RechercheCreneaux\FBInvite;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\TypeInviteAction;

if (FBForm::validParams($fbParams)) {
    $fbForm = new FBForm($fbParams, $stdEnv);

    foreach ($fbForm->getFbUsers() as $fbUser) {
        $validx = false;
        foreach ($fbParams->uids as $idx => $valuid) {
            if ($valuid['uid'] == $fbUser->uid) {
                $validx = $idx;
            }
        }
        
        if ($validx === false) {
            throw new Exception("Erreur: impossible de trouver l'uid : {$fbUser->uid} dans les paramètres");
        }

        if ($fbUser->httpError || $fbUser->valid == false) {
            $fbParams->uids[$validx]['data'] = false;
        } else {
            $fbParams->uids[$validx]['data'] =  true;
        }
    }

    $jsuids = json_encode($fbParams->uids);

    $nbResultatsAffichés = $fbForm->getFbCompare()->getNbResultatsAffichés();

    if ($nbResultatsAffichés == 0 && sizeof($fbForm->getFbUsers()) > 2) {
        $fbUserSortNbs = array_reverse(FBUtils::sortFBUsersByBusyCount(...$fbForm->getFbUsers()));

        if (!is_null($stdNewFBCompare = FBCompare::algo_search_results($fbUserSortNbs, $fbForm->getCreneauxGenerated(), $stdEnv->dtz, $fbParams->nbcreneaux))) {
            $fbForm->setFbCompare($stdNewFBCompare->fbCompare);
            $fbUsersUnsetted = $stdNewFBCompare->fbUsersUnsetted;
            $nbResultatsAffichés = $fbForm->getFbCompare()->getNbResultatsAffichés();
        }
    }

    $creneauxFinauxArray = $fbForm->getFbCompare()->getArrayCreneauxAffiches();

    $listDate = array();
    for ($i = 0; $i < $nbResultatsAffichés; $i++) {
        $creneauTmp = $creneauxFinauxArray[$i];

        $listDate[] = $creneauxFinauxArray[$i];
    }

    if ($stdEnv->wsgroup) {
        if ($fbForm->invitationProcess($listDate)) {
            $jsonSessionInviteInfos = $fbForm->fbParams->jsonSessionInviteInfos;
        }
    }

    if ($stdEnv->evento && $stdEnv->eventoShibentityid) {
        $fbEventoSession = new FBEventoSession($stdEnv->uidCasUser, $fbForm->fbParams, $fbForm->getFbUsers(), $creneauxFinauxArray);
        $isEventoSession = $fbEventoSession->donneeExistante;
    }
}
?>

<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recherche de disponibilité</title>
    <?php if ($stdEnv->prolongationBandeau === true): ?>
        <script>
            window.prolongation_ENT_args = {
                current: '<?= $stdEnv->prolongationEntArgsCurrent ?>',
                delegateAuth: true
            };
        </script>
        <script src="<?= $stdEnv->prolongationEntJs ?>"></script>
    <?php endif ?>

    <script type='text/javascript' src="build/bundle.js"></script>
</head>

<body>
    <script>
        var jstimezone = "<?= $stdEnv->dtz ?>";
        var jsduree = <?= (is_null($fbParams->duree) ? 60 : $fbParams->duree); ?>;
        let slider = document.getElementById('slider');
        <?php if ($stdEnv->wsgroup): ?>
            var urlwsgroupUserInfos = '<?= str_replace('Trusted', '', $stdEnv->urlwsgroupUserInfos); ?>';
            var urlwsgroupUsersAndGroups = '<?= $stdEnv->urlwsgroupUsersAndGroups; ?>';

            <?php if ($fbParams->uids && isset($jsuids)): ?>
                var jsuids = <?= "$jsuids" ?>;
            <?php elseif (is_null($fbParams->uids) && isset($stdEnv->uidCasUser) && strlen($stdEnv->uidCasUser) > 0): ?>
                var jsuids = [ {type: 'up1', uid: '<?= $stdEnv->uidCasUser ?>'} ];
            <?php endif ?>
            <?php if (isset($jsonSessionInviteInfos)): ?>
                var jsSessionInviteInfos = JSON.parse('<?= addslashes($jsonSessionInviteInfos) ?>');
            <?php endif ?>
            <?php if (isset($fbForm) && ! is_null($jsonSessionZoomInfos = $fbForm->fbParams->jsonSessionZoomInfos)): ?>
                var jsSessionZoomInfos = JSON.parse('<?= addslashes($jsonSessionZoomInfos) ?>');
            <?php endif ?>
        <?php endif ?>
        <?php if ($stdEnv->photoShow): ?>
            var urlwsphoto = '<?= $stdEnv->urlwsphoto; ?>';
        <?php endif ?>

        var isEventoSession = <?php echo (isset($isEventoSession) && $isEventoSession) ? "true" : "false" ?>;

        <?php if (isset($isEventoSession) && $isEventoSession): ?>
            var idEvento = "<?php echo $fbEventoSession->event['id'] ?>";
            var urlEvento = "<?php echo $fbEventoSession->event['path'] ?>";
        <?php endif ?>
    </script>

    <form id="form" class="container-lg" action="">
        <input type="hidden" name="actionFormulaireValider" value="rechercheDeCreneaux" />
            <div class="row">
                <div class="col-5 col-md-4 col-lg-3 border border-gray-500 border-dotted p-3">
                    <p>Sélection des utilisateurs</p>
                    <input class="col-11" id="person" name="person" placeholder="<?php if ($stdEnv->wsgroup): ?>Nom et/ou prenom<?php else: ?>Uid utilisateur(ex: ebohm)<?php endif ?>" />

                </div>
                <div class="col-3 border border-gray-500 border-dotted p-3">
                    <p>Nombre de créneaux</p>
                    <input id="creneaux" class="col-8" name="creneaux" type="number"
                        value="<?php print($fbParams->nbcreneaux ? $fbParams->nbcreneaux : 10) ?>" />
                </div>
                <div class="col col-md-3 col-lg-3 border-start border-top border-bottom border-gray-500 border-dotted p-3">
                    <p>Durée des créneaux</p>

                    <select id="duree" name="duree" required=true>
                        <option value="30" <?= ($fbParams->duree == 30) ? ' selected' : '' ?>>30 minutes</option>
                        <option value="60" <?= ($fbParams->duree == 60 || is_null($fbParams->duree)) ? ' selected' : '' ?>>1h</option>
                        <option value="90" <?= ($fbParams->duree == 90) ? ' selected' : '' ?>>1h30</option>
                        <option value="120" <?= ($fbParams->duree == 120) ? ' selected' : '' ?>>2h</option>
                        <option value="150" <?= ($fbParams->duree == 150) ? ' selected' : '' ?>>2h30</option>
                        <option value="180" <?= ($fbParams->duree == 180) ? ' selected' : '' ?>>3h</option>
                        <option value="210" <?= ($fbParams->duree == 210) ? ' selected' : '' ?>>3h30</option>
                        <option value="240" <?= ($fbParams->duree == 240) ? ' selected' : '' ?>>4h</option>
                        <option value="480" <?= ($fbParams->duree == 480) ? ' selected' : '' ?>>8h</option>
                        <option value="720" <?= ($fbParams->duree == 720) ? ' selected' : '' ?>>12h</option>
                    </select>
                </div>
                <div class="col-11 col-md-7 col-lg-4 order-lg-2 border border-dotted p-3">

                <?php if ($stdEnv->externalfbs): ?>
                    <div id="externalFBs">
                        <div id="refexturi" class="exturi row d-flex align-item-center d-none">
                            <label class="col-3 pe-0">Url externe</label>
                            <div class="col-5 px-0 overflow-hidden">
                                <input class="" type="text" name="externaluris[]" />
                            </div>
                            <div class="col-3">
                                <button class="addExternalFB">ajouter</button>
                            </div>
                        </div>
                    </div>
                <?php endif ?>

                    <div id="divpersonselect">
                        <br />
                        <p>Utilisateurs sélectionnés</p>
                        <p id="alertrequire" class="d-none text-danger" >Séléction minimum de 2 utilisateurs non optionnels</p>
                        <ul id="person_ul" class="px-0">
                        </ul>
                    </div>
                    <div id="participantExplicatif" class="toast align-items-end ms-auto p-3 d-none">
                            <span>* Les participants optionnels ne sont pas pris en compte dans les calculs de disponibilités</span>
                    </div>
                </div>
                <div class="col-4 col-lg-2 order-lg-4 p-3 border border-gray-500 border-dotted">
                    <p>A partir du</p>
                    <input class="col-11 col-xs-11 col-sm-10 col-md-7 col-lg-11 col-xl-9 col-xxl-8" required type="date" name="fromDate" min="<?= (new DateTime())->format('Y-m-d') ?>"
                        max="<?= (new DateTime())->add(new DateInterval('P120D'))->format('Y-m-d') ?>"
                        value="<?= $fbParams->fromDate; ?>" />
                    <p class="mt-4">Période de recherche</p>
                    <select class="col-12 col-sm-10 col-md-8" name="rechercheSurXJours" required>
                        <option value="7" <?= ($fbParams->rechercheSurXJours == 7) ? ' selected' : '' ?>>7 jours</option>
                        <option value="15" <?= ($fbParams->rechercheSurXJours == 15) ? ' selected' : '' ?>> 15 jours</option>
                        <option value="30" <?= ($fbParams->rechercheSurXJours == 30|| is_null($fbParams->rechercheSurXJours)) ? ' selected' : '' ?>>30 jours</option>
                        <option value="60" <?= ($fbParams->rechercheSurXJours == 60) ? ' selected' : '' ?>>60 jours</option>
                        <option value="120" <?= ($fbParams->rechercheSurXJours == 120) ? ' selected' : '' ?>>120 jours</option>
                    </select>
                </div>
                <div class="col-8 col-lg-6 order-lg-3 border border-gray-500 border-dotted p-3">
                    <div id="divjours">
                        <p>Jours sélectionnés</p>
                        <fieldset>
                            <input type="checkbox" name="joursCreneaux[]" value="MO" <?= in_array('MO', $fbParams->joursDemandes) ? 'checked' : '' ?> />Lundi
                            <input type="checkbox" name="joursCreneaux[]" value="TU" <?= in_array('TU', $fbParams->joursDemandes) ? 'checked' : '' ?> />Mardi
                            <input type="checkbox" name="joursCreneaux[]" value="WE" <?= in_array('WE', $fbParams->joursDemandes) ? 'checked' : '' ?> />Mercredi
                            <input type="checkbox" name="joursCreneaux[]" value="TH" <?= in_array('TH', $fbParams->joursDemandes) ? 'checked' : '' ?> />Jeudi
                            <input type="checkbox" name="joursCreneaux[]" value="FR" <?= in_array('FR', $fbParams->joursDemandes) ? 'checked' : '' ?> />Vendredi
                            <input type="checkbox" name="joursCreneaux[]" value="SA" <?= in_array('SA', $fbParams->joursDemandes) ? 'checked' : '' ?> />Samedi
                        </fieldset>
                        <br />
                        </div>
                    <div id="divplagehoraire">
                        <p>Plage horaire</p>
                        <div id="slider" class="col-12 mt-5"></div>
                        <input type='hidden' name="plagesHoraires[]" value="<?= $fbParams->plagesHoraires[0]; ?>" />
                        <input type='hidden' name="plagesHoraires[]" value="<?= $fbParams->plagesHoraires[1]; ?>" />
                    </div>
                </div>
               <div class="col col-lg-3 offset-6 offset-md-0 order-lg-1 border border-gray-500 border-dotted">
                    <p>Envoyer requête</p>
                    <input class="btn btn-sm btn-primary rounded text-wrap" type="submit" name="submitRequete" value="Recherche de disponibilité" />
                </div>
            </div>
        <?php if ($stdEnv->wsgroup): require_once 'modal.inc.php'; endif?>
        <?php if ($stdEnv->evento && $stdEnv->eventoWsUrl && $stdEnv->eventoShibentityid && isset($fbForm)): require_once 'modal_evento.inc.php'; endif?>

    <?php
    if (isset($fbParams->listUidsOptionnels) && sizeof($fbParams->listUidsOptionnels) > 0) {
        echo '<script>var jsListUidsOptionnels=' . json_encode($fbParams->listUidsOptionnels) . ';</script>';
    }
    ?>
    <div id="reponse" class="container-lg mt-4 px-0">
        <?php if (isset($fbForm)): ?>
            <?php if ($fbUsersUnsetted = $fbForm->getFBRessourcesDisqualifierOuBloquer()): ?>
                <?php $txtFailParticipants = "La recherche de créneaux sur tous les participants ayant échouée, les participants suivants sont exclus de la recherche dans le but de vous présenter un résultat"; ?>
                <div class='shadow p-3 mb-5 bg-body rounded lead'>
                    <p>
                        <?= $txtFailParticipants ?>
                    </p>
                    <ul>
                        <?php foreach ($fbUsersUnsetted as $fbUser): ?>
                            <li>
                                <?= $fbUser->getDisplayName() ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endif ?>
        <?php endif ?>

        <?php if (isset($listDate) && sizeof($listDate) == 0): ?>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
        <?php elseif (isset($listDate) && sizeof($listDate) > 0): ?>
            <?php
            $formatter_day = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE");
            $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "dd/MM/yyyy HH'h'mm");
            $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm");
            $idx=0; ?>

            <p>Créneaux disponibles</p>
            <ul id="listReponse" class="col-12 mb-1 px-0 ps-sm-4">
                <?php for ($idx = 0; $idx < count($listDate) && $date=$listDate[$idx]; $idx++): ?>
                    <li class="row">
                        <time class="col-5">
                            <span class="col-2">
                                <?= $formatter_day->format($date->startDate->getTimestamp()) ?>
                            </span>
                            <?= $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?>
                        </time>
                        <?php if ($stdEnv->wsgroup): ?>
                            <?php $invitationObj = FBInvite::invitationDejaEnvoyeSurCreneau($date, $fbForm->getFbUsers()); ?>
                            <?php $invitationFlag = $invitationObj->typeInvationAction; ?>
                            <?php if ($invitationFlag != TypeInviteAction::New):?>
                                <!-- balise a pour fonctionner avec evento  (hack, trouver une methode plus élégante ...) -->
                                <a href="#" class="d-none" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>" ></a>

                                <div class="<?= $invitationFlag == TypeInviteAction::NewParticipants ? 'col-1 offset-1 offset-md-0 justify-content-md-start px-md-0 ms-lg-4 col-xl-1 offset-xl-0 ms-xl-5 ps-xxl-4': 'col-4' ?> d-flex align-items-center justify-content-center">
                                    <div class='invitationEnvoyée'  data-bs-toggle="tooltip" data-bs-html="true"
                                        data-bs-placement="right" title="<?= FBUtils::formTooltipEnvoyéHTML($invitationObj->mails) ?>">
                                        <span class="text-success mb-3 me-5<?= $invitationFlag == TypeInviteAction::NewParticipants ? ' ms-xxl-1':''?>">Envoyé</span>
                                    </div>
                                </div>
                            <?php endif ?>

                            <?php
                                switch ($invitationFlag) {
                                    case TypeInviteAction::Exist:
                                        break;
                                    case TypeInviteAction::NewParticipants:
                                        $newParticipant = false;
                                        $labelParticipant = "Envoyer une invitation aux nouveaux participants";
                                    break;
                                    case TypeInviteAction::New:
                                        $newParticipant = true;
                                        $labelParticipant = "Envoyer une invitation aux participants";
                                    break;
                                }
                            ?>
                            <?php if ($invitationFlag !== TypeInviteAction::Exist) : ?>
                                <a href="#" class="<?= $newParticipant ? 'col-6':'col-5' ?> col-md-4 px-0 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#creneauMailInput" newParticipant="<?= $newParticipant ? 'true' : 'false' ?>" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>"><?= $labelParticipant ?></a>
                            <?php endif ?>
                        <?php endif ?>
                    </li>
                <?php endfor ?>
            </ul>
            <?php if ($stdEnv->evento && $stdEnv->eventoShibentityid): ?>
            <iframe src="https://evento.univ-paris1.fr/Shibboleth.sso/Login?entityID=<?= $stdEnv->eventoShibentityid ?>&target=/Shibboleth.sso/Session" name="evento-iframe" style="width:0;height:0;border:0; border:none;"></iframe>
            <div id="eventoDiv" class="col-8 d-flex justify-content-start pe-4">
                <input id="evento" name="evento" type="button" class="btn btn-success" data-mdb-ripple-init="" data-bs-toggle="modal" data-bs-target="#modalEvento" value="<?= $isEventoSession ? "Mettre à jour l'Evento" : "Créer un Evento" ?>" />
                <span type="button" class="col-2 btn-clipboard <?= $isEventoSession ? '' : 'd-none' ?>" title="Copier le lien">
                    <i class="bi bi-clipboard h4 d-inline-flex pt-2 ps-2"  <?= $isEventoSession ? 'data-creneau-url="' . $fbEventoSession->event['path'] . '"' : ''?> aria-hidden="true" onclick="copyClipboard(event)"></i>
                </span>
            </div>
            <?php endif ?>
        <?php endif ?>
    </div>
    </form>
</body>
</html>
