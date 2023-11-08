<?php

require 'vendor/autoload.php';

require_once('FBUser.php');
require_once('FBUtils.php');
require_once('FBCompare.php');
require_once('FBCreneauxGeneres.php');
require_once('FBInvite.php');

session_start();

// Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
$env = (isset($_ENV['ENV'])) ? $_ENV['ENV'] : 'dev';
$url = $_ENV['URL_FREEBUSY'];
$dtz = $_ENV['TIMEZONE'];
$urlwsgroup = $_ENV['URLWSGROUP'];
setlocale(LC_TIME, $_ENV['LOCALE']);
date_default_timezone_set($dtz);

$varsHTTPGet = filter_var_array($_GET);

$actionFormulaireValider = isset($varsHTTPGet['actionFormulaireValider']) ? $varsHTTPGet['actionFormulaireValider'] : 'rechercheDeCreneaux';
$uids = isset($varsHTTPGet['listuids']) ? $varsHTTPGet['listuids'] : null;
$nbcreneaux = isset($varsHTTPGet['creneaux']) ? $varsHTTPGet['creneaux'] : null;
$duree = isset($varsHTTPGet['duree']) ? $varsHTTPGet['duree'] : null;
$plagesHoraires = isset($varsHTTPGet['plagesHoraires']) ? $varsHTTPGet['plagesHoraires'] : array('9-12', '14-17');
$joursDemandes = isset($varsHTTPGet['joursCreneaux']) ? $varsHTTPGet['joursCreneaux'] : array('MO', 'TU', 'WE', 'TH', 'FR');
$fromDate = isset($varsHTTPGet['fromDate']) ? $varsHTTPGet['fromDate'] : (new DateTime())->format('Y-m-d');
$titleEvent = isset($varsHTTPGet['titrecreneau']) ? $varsHTTPGet['titrecreneau'] : null;
$descriptionEvent = isset($varsHTTPGet['summarycreneau']) ? $varsHTTPGet['summarycreneau'] : null;
$lieuEvent = isset($varsHTTPGet['lieucreneau']) ? $varsHTTPGet['lieucreneau'] : null;
$modalCreneauStart = isset($varsHTTPGet['modalCreneauStart']) ? $varsHTTPGet['modalCreneauStart'] : null;
$modalCreneauEnd = isset($varsHTTPGet['modalCreneauEnd']) ? $varsHTTPGet['modalCreneauEnd'] : null;

if (($uids && sizeof($uids) > 1) && ($plagesHoraires && sizeof($plagesHoraires) > 0) && $nbcreneaux && $duree) {
    $js_uids = json_encode($uids);

    $creneauxGenerated = (new FBCreneauxGeneres($fromDate, $duree, $plagesHoraires, $dtz, $joursDemandes))->getCreneauxSeq();

    $fbUsers = array();
    foreach ($uids as $uid) {
        $fbUsers[] = FBUser::factory($uid, $dtz, $url, $duree, $creneauxGenerated);
        //    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
    }
    $fbCompare = new FBCompare($fbUsers, $creneauxGenerated, $dtz, $nbcreneaux);
    $nbResultatsAffichés = $fbCompare->getNbResultatsAffichés();
    $creneauxFinauxArray = $fbCompare->getArrayCreneauxAffiches();

    $listDate = array();
    for ($i = 0; $i < $nbResultatsAffichés; $i++) {
        $creneauTmp = $creneauxFinauxArray[$i];

        $listDate[] = $creneauxFinauxArray[$i];
    }

    if ($actionFormulaireValider == 'envoiInvitation' && is_null($titleEvent) == false && is_null($descriptionEvent) == false && is_null($lieuEvent) == false && is_null($modalCreneauStart) == false && is_null($modalCreneauEnd) == false) {
        $fbInvite = new FBInvite($uids, $urlwsgroup, $modalCreneauStart, $modalCreneauEnd, $titleEvent, $descriptionEvent, $lieuEvent, $dtz, $listDate, $varsHTTPGet);
        $fbInvite->sendInvite();
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

<body>
    <div id="titre">
        <h1>Recherche de disponibilités</h1>
    </div>
    <div id="formulaire">
        <form id="form" action="">
            <input type="hidden" name="actionFormulaireValider" value="rechercheDeCreneaux" />
            <table>
                <tr>
                    <td>
                        <p>Séléction des utilisateurs</p>
                        <input id="person" name="person" placeholder="Nom et/ou prenom" />

                        <script>
                            var jsduree = <?= (is_null($duree) ? 30 : $duree); ?>;
                            var urlwsgroup = '<?= $urlwsgroup; ?>';

                            <?php if (isset($duree) && !is_null($duree)) : ?>
                                $(function() {
                                    $('#duree option[value="<?= $duree ?>"').prop('selected', true);
                                });
                            <?php endif ?>

                            <?php if ($uids && isset($js_uids)) : ?>
                                var jsuids = <?= "$js_uids" ?>;

                                $(function() {
                                    setOptionsUid(jsuids);

                                    if (jsuids.length < 2) {
                                        errorShow(true);
                                    }
                                });
                            <?php endif ?>
                        </script>
                    </td>
                    <td>
                        <p>Nombre de créneaux</p>
                        <input id="creneaux" name="creneaux" type="number" value="<?php print($nbcreneaux ? $nbcreneaux : 3) ?>" />
                    </td>
                    <td>
                        <p>Durée des créneaux</p>

                        <select id="duree" name="duree" required=true>
                            <option value="30">30 minutes</option>
                            <option value="60">1h</option>
                            <option value="90">1h30</option>
                            <option value="120">2h</option>
                            <option value="150">2h30</option>
                            <option value="180">3h</option>
                            <option value="210">3h30</option>
                            <option value="240">4h</option>
                        </select>
                    </td>
                    <td>
                        <p>Envoyer requête</p>
                        <input type="submit" name="submitRequete" value="Recherche de disponibilité" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="divpersonselect">
                            <br />
                            <p>Utilisateurs sélectionnés</p>
                            <p class="alertrequire">Séléction minimum de 2 utilisateurs</p>
                            <ul id="person_ul">
                            </ul>
                        </div>
                    </td>
                    <td colspan="2">
                        <div id="divjours">
                            <p>Jours séléctionnés</p>
                            <fieldset>
                                <input type="checkbox" name="joursCreneaux[]" value="MO" <?php if (in_array('MO', $joursDemandes)) echo 'checked' ?>>Lundi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="TU" <?php if (in_array('TU', $joursDemandes)) echo 'checked' ?>>Mardi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="WE" <?php if (in_array('WE', $joursDemandes)) echo 'checked' ?>>Mercredi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="TH" <?php if (in_array('TH', $joursDemandes)) echo 'checked' ?>>Jeudi</input>
                                <input type="checkbox" name="joursCreneaux[]" value="FR" <?php if (in_array('FR', $joursDemandes)) echo 'checked' ?>>Vendredi</input>
                            </fieldset>
                            <br />
                        </div>
                        <div id="divplagehoraire">
                            <p>Plage horaire</p>
                            <div id="slider"></div>
                            <input type='hidden' name="plagesHoraires[]" value="<?= $plagesHoraires[0]; ?>" />
                            <input type='hidden' name="plagesHoraires[]" value="<?= $plagesHoraires[1]; ?>" />
                        </div>
                    </td>
                    <td>
                        <div id="divfromdate">
                            <p>A partir du</p>
                            <input type="date" name="fromDate" value="<?= $fromDate; ?>" />
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
                            <div class="col-sm-6" id="creneauBoxDesc">
                                <p>Créneau</p>
                                <span id="creneauInfo" class="text-nowrap text-break"></span>
                                <hr>
                                <p>Participants</p>
                                <ul id="creneauMailParticipant_ul" />
                            </div>
                            <div class="col-sm-5 align-content-between" id="creneauBoxInput">
                                <label for="titrecreneau">Titre de l'évenement</label>
                                <input id="titrecreneau" type="text" disabled placeholdr="Titre de l'évenement" name="titrecreneau" value="<?= $titleEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un titre pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                                <label for="summarycreneau">Description :</label>
                                <textarea id="summarycreneau" disabled placeholdr="Description" name="summarycreneau" value="<?= $descriptionEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner une description pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')"><?= $descriptionEvent; ?> </textarea>
                                <label for="lieucreneau">Lieu :</label>
                                <input id="lieucreneau" type="text" disabled placeholder="Lieu de l'évenement" name="lieucreneau" value="<?= $lieuEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un lieu pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                            </div>
                            <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                            <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
                        </div>
                        <div class="modal-footer" id="creneauBoxFooter">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" />
                        </div>
                        <?php if ($env != 'prod') : ?>
                            <div>
                                <p>DEBUG environnement. Mail recepteur des tests </p>
                                <input type="text" name="debugmail" />
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php if (isset($listDate) && sizeof($listDate) > 0) : ?>
        <?php $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd/MM/yyyy HH'h'mm");
        $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm") ?>
        <div id="reponse">
            <p>Créneaux disponibles</p>
            <ul>
                <?php foreach ($listDate as $key => $date) : ?>
                    <li>
                        <time><?= $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?></time>
                        <?php if (FBInvite::invitationDejaEnvoyeSurCreneau($key, $date)) : ?>
                            <div class='invitationEnvoyée'>
                                <span class="text-success">Envoyé</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="green" class="bi bi-check2-circle" viewBox="0 0 16 16">
                                    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z" />
                                    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z" />
                                </svg>
                            </div>
                        <?php else: ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#creneauMailInput" timeStart="<?= $date->startDate->getTimestamp() ?>" timeEnd="<?= $date->endDate->getTimestamp() ?>">Envoyer une invitation aux participants</a>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php elseif (isset($listDate) && sizeof($listDate) == 0) : ?>
        <div>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
        </div>
    <?php endif ?>

    <?php if ($actionFormulaireValider == 'envoiInvitation' && isset($fbInvite) && $fbInvite->getMailEffectivementEnvoye() && ($mailEnvoyesStr = $fbInvite->getMailsEnvoyes()) && (strlen($mailEnvoyesStr) > 0)) : ?>
        <script langage='javascript'>
            alert("Mails invitation envoyés à : <?= $mailEnvoyesStr ?>");
        </script>
    <?php endif ?>
</body>

</html>