<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;
use Exception;
use DateInterval;
use Dotenv\Dotenv;
use IntlDateFormatter;
use RechercheCreneaux\FBForm;
use RechercheCreneaux\FBUtils;
use RechercheCreneaux\FBInvite;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\TypeInviteAction;

require 'vendor/autoload.php';

session_start();

// Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
Dotenv::createImmutable(__DIR__)->safeLoad();
setlocale(LC_TIME, $_ENV['LOCALE']);

$stdEnv = new stdClass();
$stdEnv->env = (isset($_ENV['ENV'])) ? $_ENV['ENV'] : 'dev';
$stdEnv->url = $_ENV['URL_FREEBUSY'];
$stdEnv->dtz = $_ENV['TIMEZONE'];
$stdEnv->urlwsgroup = $_ENV['URLWSGROUP'];
$stdEnv->urlwsphoto = $_ENV['URLWSPHOTO'];

$stdEnv->maildebuginvite = ($stdEnv->env == 'dev' && isset($_ENV['MAIL_DEV_SEND_DEBUG'])) ? $_ENV['MAIL_DEV_SEND_DEBUG'] : null;

date_default_timezone_set($stdEnv->dtz);

$stdEnv->varsHTTPGet = filter_var_array($_GET);

$stdParams = new stdClass();
$stdParams->actionFormulaireValider = isset($stdEnv->varsHTTPGet['actionFormulaireValider']) ? $stdEnv->varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
$stdParams->uids = isset($stdEnv->varsHTTPGet['listuids']) ? $stdEnv->varsHTTPGet['listuids'] : null;
$stdParams->nbcreneaux = isset($stdEnv->varsHTTPGet['creneaux']) ? (int) $stdEnv->varsHTTPGet['creneaux'] : null;
$stdParams->duree = isset($stdEnv->varsHTTPGet['duree']) ? (int) $stdEnv->varsHTTPGet['duree'] : null;
$stdParams->plagesHoraires = isset($stdEnv->varsHTTPGet['plagesHoraires']) ? $stdEnv->varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
$stdParams->joursDemandes = isset($stdEnv->varsHTTPGet['joursCreneaux']) ? $stdEnv->varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
$stdParams->fromDate = isset($stdEnv->varsHTTPGet['fromDate']) ? $stdEnv->varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
$stdParams->titleEvent = isset($stdEnv->varsHTTPGet['titrecreneau']) ? $stdEnv->varsHTTPGet['titrecreneau'] : null;
$stdParams->descriptionEvent = isset($stdEnv->varsHTTPGet['summarycreneau']) ? $stdEnv->varsHTTPGet['summarycreneau'] : null;
$stdParams->lieuEvent = isset($stdEnv->varsHTTPGet['lieucreneau']) ? $stdEnv->varsHTTPGet['lieucreneau'] : null;
$stdParams->modalCreneauStart = isset($stdEnv->varsHTTPGet['modalCreneauStart']) ? $stdEnv->varsHTTPGet['modalCreneauStart'] : null;
$stdParams->modalCreneauEnd = isset($stdEnv->varsHTTPGet['modalCreneauEnd']) ? $stdEnv->varsHTTPGet['modalCreneauEnd'] : null;
$stdParams->listUidsOptionnels = isset($stdEnv->varsHTTPGet['listUidsOptionnels']) ? $stdEnv->varsHTTPGet['listUidsOptionnels'] : null;
$stdParams->jsonSessionInfos = isset($_SESSION['inviteEnregistrement']) ? json_encode($_SESSION['inviteEnregistrement']) : null;



if (FBForm::validParams($stdParams)) {
    $js_uids = json_encode($stdParams->uids);

    $fbForm = new FBForm($stdParams, $stdEnv);

    $nbResultatsAffichés = $fbForm->getFbCompare()->getNbResultatsAffichés();

    if ($nbResultatsAffichés == 0 && sizeof($fbForm->getFbUsers()) > 2) {
        $fbUserSortNbs = array_reverse(FBUtils::sortFBUsersByBusyCount(... $fbForm->getFbUsers()));

        if (!is_null($stdNewFBCompare = FBCompare::algo_search_results($fbUserSortNbs, $fbForm->getCreneauxGenerated(), $stdEnv->dtz, $stdParams->nbcreneaux))) {
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

    if (FBInvite::verifSiInvitation($stdParams)) {
        $fbInvite = new FBInvite($fbForm, $stdParams, $stdEnv, $listDate);
        $fbInvite->sendInvite();
        // Lors d'un premier appel, initialisation de jsonSessionInfos
        if ($stdParams->jsonSessionInfos == null) {
            if (!isset($_SESSION['inviteEnregistrement'])) {
                throw new Exception('Erreur session inviteEnregistrement null sur form.php');
            }
            $stdParams->jsonSessionInfos = json_encode($_SESSION['inviteEnregistrement']);
        }
    }
}
?>

<!DOCTYPE html>

<head>
    <link href="./css/bootstrap.min.css" rel="stylesheet" />
    <script src="./js/bootstrap.bundle.min.js"></script>

    <script src="./js/jquery.min.js"></script>
    <script type='text/javascript' src="https://wsgroups.univ-paris1.fr/web-widget/autocompleteUser-resources.html.js"></script>

    <link href="./css/form.css" rel="stylesheet" />
    <script type='text/javascript' src='./js/form.js'></script>

    <link href="./css/nouislider.min.css" rel="stylesheet" />
    <script src="./js/nouislider.min.js"></script>

    <script src="./js/min/moment.js"></script>
    <script src="./js/min/moment-with-locales.js"></script>
</head>

<body >
    <div id="titre">
        <h1>Recherche de disponibilités</h1>
    </div>
    <div id="formulaire">
        <form id="form" action="">
            <input type="hidden" name="actionFormulaireValider" value="rechercheDeCreneaux" />
            <table>
                <tr>
                    <td class="col-5">
                        <p>Séléction des utilisateurs</p>
                        <input id="person" name="person" placeholder="Nom et/ou prenom" />

                        <script>
                            var jsduree = <?= (is_null($stdParams->duree) ? 30 : $stdParams->duree); ?>;
                            var urlwsgroup = '<?= $stdEnv->urlwsgroup; ?>';
                            var urlwsphoto = '<?= $stdEnv->urlwsphoto; ?>';

                            <?php if (isset($stdParams->duree) && !is_null($stdParams->duree)) : ?>
                                $(function() {
                                    $('#duree option[value="<?= $stdParams->duree ?>"').prop('selected', true);
                                });
                            <?php endif ?>

                            <?php if ($stdParams->uids && isset($js_uids)) : ?>
                                var jsuids = <?= "$js_uids" ?>;

                                $(function() {
                                    setOptionsUid(jsuids);

                                    if (jsuids.length < 2) {
                                        errorShow(true);
                                    }
                                });
                            <?php endif ?>
                            <?php if (isset($stdParams->jsonSessionInfos)): ?>
                                var jsSessionInfos=JSON.parse('<?= $stdParams->jsonSessionInfos ?>');
                            <?php endif ?>
                        </script>
                    </td>
                    <td>
                        <p>Nombre de créneaux</p>
                        <input id="creneaux" name="creneaux" type="number" value="<?php print($stdParams->nbcreneaux ? $stdParams->nbcreneaux : 3) ?>" />
                    </td>
                    <td>
                        <p>Durée des créneaux</p>

                        <select id="duree" name="duree" required=true>
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1h</option>
                            <option value="90">1h30</option>
                            <option value="120">2h</option>
                            <option value="150">2h30</option>
                            <option value="180">3h</option>
                            <option value="210">3h30</option>
                            <option value="240">4h</option>
                        </select>
                    </td>
                    <td class="col-2">
                        <p>Envoyer requête</p>
                        <input type="submit" name="submitRequete" value="Recherche de disponibilité" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="divpersonselect">
                            <br />
                            <p>Utilisateurs sélectionnés</p>
                            <p class="alertrequire">Séléction minimum de 2 utilisateurs non optionnels</p>
                            <ul id="person_ul" class="px-0">
                            </ul>
                        </div>
                    </td>
                    <td colspan="2">
                        <div id="divjours">
                            <p>Jours séléctionnés</p>
                            <fieldset>
                                <input type="checkbox" name="joursCreneaux[]" value="MO" <?php if (in_array('MO', $stdParams->joursDemandes)) echo 'checked' ?>>Lundi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="TU" <?php if (in_array('TU', $stdParams->joursDemandes)) echo 'checked' ?>>Mardi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="WE" <?php if (in_array('WE', $stdParams->joursDemandes)) echo 'checked' ?>>Mercredi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="TH" <?php if (in_array('TH', $stdParams->joursDemandes)) echo 'checked' ?>>Jeudi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="FR" <?php if (in_array('FR', $stdParams->joursDemandes)) echo 'checked' ?>>Vendredi</input>
                            </fieldset>
                            <br />
                        </div>
                        <div id="divplagehoraire">
                            <p>Plage horaire</p>
                            <div id="slider"></div>
                            <input type='hidden' name="plagesHoraires[]" value="<?= $stdParams->plagesHoraires[0]; ?>" />
                            <input type='hidden' name="plagesHoraires[]" value="<?= $stdParams->plagesHoraires[1]; ?>" />
                        </div>
                    </td>
                    <td>
                        <div id="divfromdate">
                            <p>A partir du</p>
                            <input required type="date" name="fromDate" min="<?= (new DateTime())->format('Y-m-d') ?>" max="<?= (new DateTime())->add(new DateInterval('P120D'))->format('Y-m-d') ?>" value="<?= $stdParams->fromDate; ?>" />
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Modal -->
            <div class="modal fade" id="creneauMailInput" tabindex="-1" aria-labelledby="modalInputLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <p>Envoi invitation aux participants</p>
                        </div>
                        <div class="row">
                            <div class="col-6" id="creneauBoxDesc">
                                <p>Créneau</p>
                                <span id="creneauInfo" class="text-nowrap text-break"></span>
                                <hr>
                                <p>Participants</p>
                                <ul id="creneauMailParticipant_ul" />
                            </div>
                            <div class="col-5 align-content-between" id="creneauBoxInput">
                                <label for="titrecreneau">Titre de l'évenement</label>
                                <input id="titrecreneau" type="text" disabled placeholder="Titre de l'évenement" name="titrecreneau" value="<?= $stdParams->titleEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un titre pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                                <label for="summarycreneau">Description :</label>
                                <textarea id="summarycreneau" disabled placeholder="Description de l'évenement" name="summarycreneau" value="<?= $stdParams->descriptionEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner une description pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $stdParams->descriptionEvent; ?></textarea>
                                <label for="lieucreneau">Lieu :</label>
                                <input id="lieucreneau" type="text" disabled placeholder="Lieu de l'évenement" name="lieucreneau" value="<?= $stdParams->lieuEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un lieu pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                            </div>
                            <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                            <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
                        </div>
                        <div class="modal-footer" id="creneauBoxFooter">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" />
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php
    if (isset($fbForm)) {
        $fullBloqueUids=array();
        foreach ($fbForm->getFbUsers() as $fbUser) {
            if ($fbUser->getEstFullBloquer()) {
                $fullBloqueUids[] = $fbUser->uid;
                $aAgendaFullBloquerStr[] = "le participant {$fbUser->getUidInfos()->displayName} n'a aucun créneaux disponibles, les résultats ne prennent pas en compte son agenda";
            }
        }
    }

    if (isset($fbUsersUnsetted) && count($fbUsersUnsetted) > 0) {
        $aUserUnsetStr[] = "La recherche de créneaux sur tous les participants ayant échouée, les participants suivants sont exclus de la recherche dans le but de vous présenter un résultat";
        foreach ($fbUsersUnsetted as $fbUserUnset) {
            $aUserUnsetStr[] = "Les résultats ne prennent pas en compte le participant {$fbUserUnset->getUidInfos()->displayName}";
        }
    }

    if (isset($stdParams->listUidsOptionnels) && sizeof($stdParams->listUidsOptionnels) > 0) {
        echo '<script>var jsListUidsOptionnels='. json_encode($stdParams->listUidsOptionnels) . ';</script>';
    }
    ?>
    <div id="reponse">
    <?php if (isset($aAgendaFullBloquerStr) && count($aAgendaFullBloquerStr) > 0): ?>
            <p class='shadow p-3 mb-5 bg-body rounded text-center lead'><?= implode("<br />", $aAgendaFullBloquerStr); ?></p>
    <?php endif ?>

    <?php if (isset($aUserUnsetStr) && count($aUserUnsetStr) > 1): ?>
            <p class='shadow p-3 mb-5 bg-body rounded text-left lead'><?= implode("<br />", $aUserUnsetStr); ?></p>
    <?php endif ?>

    <?php if (isset($listDate) && sizeof($listDate) == 0) : ?>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
    <?php elseif (isset($listDate) && sizeof($listDate) > 0) : ?>
        <?php
        $formatter_day =  IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE");
        $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "dd/MM/yyyy HH'h'mm");
        $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm") ?>
            <p>Créneaux disponibles</p>
            <ul class="col-11">
                <?php foreach ($listDate as $date) : ?>
                    <li class="row">
                        <time class="col-5"><span class="d-inline-block col-2"><?= $formatter_day->format($date->startDate->getTimestamp()) ?></span> <?= $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?></time>
                        <?php if (($invitationFlag = FBInvite::invitationDejaEnvoyeSurCreneau($date, $fbForm->getFbUsers()))->typeInvationAction != TypeInviteAction::New) : ?>
                            <div class='col-1 px-0 invitationEnvoyée' data-toggle="tooltip" data-html="true" data-bs-placement="right" title="<?= FBUtils::formTooltipEnvoyéHTML($invitationFlag->mails) ?>">
                                <span class="text-success">Envoyé</span>
                                <svg class="bi bi-check2-circle d-inline-block" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="green" viewBox="0 0 16 16">
                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z" />
                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z" />
                                </svg>
                            </div>
                        <?php endif ?>
                        <?php if ($invitationFlag->typeInvationAction == TypeInviteAction::New): ?>
                            <a href="#" class="col px-0" data-bs-toggle="modal" data-bs-target="#creneauMailInput" newParticipant="false" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>">Envoyer une invitation aux participants</a>
                        <?php elseif ($invitationFlag->typeInvationAction == TypeInviteAction::NewParticipants): ?>
                            <a href="#" class="col px-0" data-bs-toggle="modal" data-bs-target="#creneauMailInput" newParticipant="true" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>">Envoyer une invitation aux nouveaux participants</a>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div>
</body>

</html>