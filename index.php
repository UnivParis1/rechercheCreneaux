<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use DateInterval;
use IntlDateFormatter;
use RechercheCreneaux\FBForm;
use RechercheCreneaux\FBUtils;
use RechercheCreneaux\FBInvite;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\TypeInviteAction;

require_once("head.php");

if (FBForm::validParams($fbParams)) {
    $js_uids = json_encode($fbParams->uids);

    $fbForm = new FBForm($fbParams, $stdEnv);

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
    <?php if ($stdEnv->prolongationBandeau === true): ?>
        <script>
            window.prolongation_ENT_args = {
                current: '<?= $stdEnv->prolongationEntArgsCurrent ?>',
                delegateAuth: true
            };
        </script>
        <script src="<?= $stdEnv->prolongationEntJs ?>"></script>
    <?php endif ?>

    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="node_modules/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet" />
    <link href="./css/form.css" rel="stylesheet" />

    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="node_modules/jquery/dist/jquery.min.js"></script>

    <link href="node_modules/nouislider/dist/nouislider.min.css" rel="stylesheet" />
    <script src="node_modules/nouislider/dist/nouislider.min.js"></script>
    <script src="js/slider.js"></script>

    <script src="node_modules/moment/min/moment.min.js"></script>
    <script src="node_modules/moment/min/moment-with-locales.js"></script>

    <?php if ($stdEnv->wsgroup): ?>
        <script type='text/javascript' src="https://wsgroups.univ-paris1.fr/web-widget/autocompleteUser-resources.html.js"></script>
        <script type='text/javascript' src='./js/form.js'></script>
    <?php else: ?>
        <script type='text/javascript' src='./js/noform.js'></script>
    <?php endif ?>
    <?php if ($stdEnv->evento && $stdEnv->eventoWsUrl && $stdEnv->eventoShibentityid): ?>
        <script type='text/javascript' src='./js/evento.js'></script>
    <?php endif ?>
</head>

    <body>
    <form id="form" class="container" action="">
        <input type="hidden" name="actionFormulaireValider" value="rechercheDeCreneaux" />
        <div class="row">
            <div class="col">
                <p>Séléction des utilisateurs</p>
                <input id="person" name="person" placeholder="<?php if ($stdEnv->wsgroup): ?>Nom et/ou prenom<?php else: ?>Uid utilisateur(ex: ebohm)<?php endif ?>" />

    <script>
        var jsduree = <?= (is_null($fbParams->duree) ? 60 : $fbParams->duree); ?>;
        let slider = document.getElementById('slider');
        <?php if ($stdEnv->wsgroup): ?>
            var urlwsgroupUserInfos = '<?= $stdEnv->urlwsgroupUserInfos; ?>';
            var urlwsgroupUsersAndGroups = '<?= $stdEnv->urlwsgroupUsersAndGroups; ?>';

            <?php if ($fbParams->uids && isset($js_uids)): ?>
                var jsuids = <?= "$js_uids" ?>;

                $(function () {
                    setOptionsUid(jsuids);

                    if (jsuids.length < 2) {
                        errorShow(true);
                    }
                });
            <?php elseif (is_null($fbParams->uids) && isset($stdEnv->uidCasUser) && strlen($stdEnv->uidCasUser) > 0): ?>
                $(function () {
                    setOptionsUid(['<?= $stdEnv->uidCasUser ?>']);
                });
            <?php endif ?>
            <?php if (isset($jsonSessionInviteInfos)): ?>
                var jsSessionInviteInfos = JSON.parse('<?= $jsonSessionInviteInfos ?>');
            <?php endif ?>
            <?php if (isset($fbForm) && ! is_null($jsonSessionZoomInfos = $fbForm->fbParams->jsonSessionZoomInfos)): ?>
                var jsSessionZoomInfos = JSON.parse('<?= $jsonSessionZoomInfos ?>');
            <?php endif ?>
        <?php else: // sans wsgroup?>
            <?php if ($fbParams->uids && isset($js_uids)): ?>
                var jsuids = <?= "$js_uids" ?>;

                $(function () {
                    setOptionsUid(jsuids);
                });
            <?php endif ?>
        <?php endif ?>

        <?php if ($stdEnv->photoShow): ?>
            var urlwsphoto = '<?= $stdEnv->urlwsphoto; ?>';
        <?php endif ?>

        <?php if (isset($fbParams->duree) && !is_null($fbParams->duree)): ?>
            $(function () {
                $('#duree option[value="<?= $fbParams->duree ?>"').prop('selected', true);
            });
        <?php endif ?>

        var isEventoSession = <?php echo (isset($isEventoSession) && $isEventoSession) ? "true" : "false" ?>;

        <?php if (isset($isEventoSession) && $isEventoSession): ?>
            var idEvento = "<?php echo $fbEventoSession->event['id'] ?>";
            var urlEvento = "<?php echo $fbEventoSession->event['path'] ?>";
        <?php endif ?>
    </script>
            </div>
            <div class="col">
                <p>Nombre de créneaux</p>
                <input id="creneaux" name="creneaux" type="number"
                    value="<?php print($fbParams->nbcreneaux ? $fbParams->nbcreneaux : 3) ?>" />
            </div>
            <div class="col">
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
                </select>
            </div>
            <div class="col-2 overflow-scroll">
                <p>Envoyer requête</p>
                <input class="btn btn-sm btn-primary rounded" type="submit" name="submitRequete" value="Recherche de disponibilité" />
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                <div id="divpersonselect">
                    <br />
                    <p>Utilisateurs sélectionnés</p>
                    <p class="alertrequire">Séléction minimum de 2 utilisateurs non optionnels</p>
                    <ul id="person_ul" class="px-0">
                    </ul>
                </div>
            </div>
            <div class="col-6">
                <div id="divjours">
                    <p>Jours séléctionnés</p>
                    <fieldset>
                        <input type="checkbox" name="joursCreneaux[]" value="MO" <?php if (in_array('MO', $fbParams->joursDemandes))
                            echo 'checked' ?>>Lundi</input>
                            <input type="checkbox" name="joursCreneaux[]" value="TU" <?php if (in_array('TU', $fbParams->joursDemandes))
                            echo 'checked' ?>>Mardi</input>
                            <input type="checkbox" name="joursCreneaux[]" value="WE" <?php if (in_array('WE', $fbParams->joursDemandes))
                            echo 'checked' ?>>Mercredi</input>
                            <input type="checkbox" name="joursCreneaux[]" value="TH" <?php if (in_array('TH', $fbParams->joursDemandes))
                            echo 'checked' ?>>Jeudi</input>
                            <input type="checkbox" name="joursCreneaux[]" value="FR" <?php if (in_array('FR', $fbParams->joursDemandes))
                            echo 'checked' ?>>Vendredi</input>
                            <input type="checkbox" name="joursCreneaux[]" value="SA" <?php if (in_array('SA', $fbParams->joursDemandes))
                            echo 'checked' ?>>Samedi</input>
                        </fieldset>
                        <br />
                    </div>
                    <div id="divplagehoraire">
                        <p>Plage horaire</p>
                        <div id="slider"></div>
                        <input type='hidden' name="plagesHoraires[]" value="<?= $fbParams->plagesHoraires[0]; ?>" />
                    <input type='hidden' name="plagesHoraires[]" value="<?= $fbParams->plagesHoraires[1]; ?>" />
                </div>
            </div>
            <div class="col-2 d-inline-flex flex-column justify-content-center align-items-start fw-bold">
                <p>A partir du</p>
                <input class="col-7" required type="date" name="fromDate" min="<?= (new DateTime())->format('Y-m-d') ?>"
                    max="<?= (new DateTime())->add(new DateInterval('P120D'))->format('Y-m-d') ?>"
                    value="<?= $fbParams->fromDate; ?>" />
                <p class="mt-4">Période de recherche</p>
                <select class="col-7" name="rechercheSurXJours" required>
                    <option value="7" <?= ($fbParams->rechercheSurXJours == 7) ? ' selected' : '' ?>>7 jours</option>
                    <option value="15" <?= ($fbParams->rechercheSurXJours == 15) ? ' selected' : '' ?>> 15 jours</option>
                    <option value="30" <?= ($fbParams->rechercheSurXJours == 30|| is_null($fbParams->rechercheSurXJours)) ? ' selected' : '' ?>>30 jours</option>
                    <option value="60" <?= ($fbParams->rechercheSurXJours == 60) ? ' selected' : '' ?>>60 jours</option>
                    <option value="120" <?= ($fbParams->rechercheSurXJours == 120) ? ' selected' : '' ?>>120 jours</option>
                </select>
            </div>
        </div>

        <?php if ($stdEnv->wsgroup): require_once('modal.inc.php'); endif?>
        <?php if ($stdEnv->evento && $stdEnv->eventoWsUrl && $stdEnv->eventoShibentityid && isset($fbForm)): require_once('modal_evento.inc.php'); endif?>

    <?php
    if (isset($fbParams->listUidsOptionnels) && sizeof($fbParams->listUidsOptionnels) > 0) {
        echo '<script>var jsListUidsOptionnels=' . json_encode($fbParams->listUidsOptionnels) . ';</script>';
    }
    ?>
    <div id="reponse" class="container my-4">
        <?php if (isset($fbForm)): ?>
            <?php if ($fbUsersUnsetted = $fbForm->getFBUsersDisqualifierOrBloquer()): ?>
                <?php $txtFailParticipants = "La recherche de créneaux sur tous les participants ayant échouée, les participants suivants sont exclus de la recherche dans le but de vous présenter un résultat"; ?>
                <div class='shadow p-3 mb-5 bg-body rounded lead'>
                    <p>
                        <?= $txtFailParticipants ?>
                    </p>
                    <ul>
                        <?php foreach ($fbUsersUnsetted as $fbUser): ?>
                            <li>
                                <?= $fbUser->getUidInfos()->displayName ?>
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
            <ul id="listReponse" class="col-11 mb-1">
                <?php for ($idx = 0; $idx < count($listDate) && $date=$listDate[$idx]; $idx++): ?>
                    <li class="row d-flex">
                        <time class="col-5"><span class="col-2">
                                <?= $formatter_day->format($date->startDate->getTimestamp()) ?>
                            </span>
                            <?= $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?>
                        </time>
                        <?php if ($stdEnv->wsgroup): ?>
                            <?php if (($invitationFlag = FBInvite::invitationDejaEnvoyeSurCreneau($date, $fbForm->getFbUsers()))->typeInvationAction != TypeInviteAction::New ): ?>
                                <!-- balise a pour fonctionner avec evento  (hack, trouver une methode plus élégante ...) -->
                                <a href="#" class="d-none" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>" ></a>
                                <div class='col-1 px-0 invitationEnvoyée' data-toggle="tooltip" data-html="true"
                                    data-bs-placement="right" title="<?= FBUtils::formTooltipEnvoyéHTML($invitationFlag->mails) ?>">
                                    <span class="text-success">Envoyé</span>
                                    <span class="bi bi-check2-circle d-inline-block"></span>
                                </div>
                            <?php endif ?>
                            <?php if ($invitationFlag->typeInvationAction == TypeInviteAction::New ): ?>
                                <a href="#" class="col-4 px-0" data-bs-toggle="modal" data-bs-target="#creneauMailInput" newParticipant="false" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>">Envoyer une invitation aux participants</a>
                            <?php elseif ($invitationFlag->typeInvationAction == TypeInviteAction::NewParticipants): ?>
                                <a href="#" class="col-4 px-0" data-bs-toggle="modal" data-bs-target="#creneauMailInput" newParticipant="true" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>">Envoyer une invitation aux nouveaux participants</a>
                            <?php endif ?>
                        <?php endif ?>
                    </li>
                <?php endfor ?>
            </ul>
            <?php if ($stdEnv->evento && $stdEnv->eventoShibentityid): ?>
            <iframe src="https://evento.univ-paris1.fr/Shibboleth.sso/Login?entityID=<?= $stdEnv->eventoShibentityid ?>&target=/Shibboleth.sso/Session" name="evento-iframe" style="width:0;height:0;border:0; border:none;"></iframe>
            <div id="eventoDiv" class="col-8 d-flex justify-content-start pe-4">
                <input id="evento" name="evento" type="button" class="btn btn-success" data-mdb-ripple-init="" data-bs-toggle="modal" data-bs-target="#modalEvento" value="<?= $isEventoSession ? "Mettre à jour l'Evento" : "Créer un Evento" ?>" />
            </div>
            <?php endif ?>
        <?php endif ?>
    </div>
    </form>
</body>

</html>
