<?php

require 'vendor/autoload.php';

require_once('FBUser.php');
require_once('FBUtils.php');
require_once('FBCompare.php');
require_once('FBCreneauxGeneres.php');

// Variable dans .env initialisées ENV, URL_FREEBUSY pour l'appel aux agendas, TIMEZONE et LOCALE
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
$env=(isset($_ENV['ENV'])) ? $_ENV['ENV'] : 'dev';
$url=$_ENV['URL_FREEBUSY'];
$dtz=$_ENV['TIMEZONE'];
$urlwsgroup=$_ENV['URLWSGROUP'];
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

    if ($actionFormulaireValider == 'envoiInvitation' && is_null($descriptionEvent) == false && is_null($lieuEvent) == false && !is_null($modalCreneauStart) && !is_null($modalCreneauEnd)) {
        $listUserinfos = FBUtils::requestUidsNames($uids, $urlwsgroup);
        $icsData = FBUtils::icalCreationInvitation($listUserinfos, $modalCreneauStart, $modalCreneauEnd, $descriptionEvent, $lieuEvent, $urlwsgroup, $dtz);
        $sujet = "Invitation évenement";

        $alertMailsEnvoyes = array();
        foreach ($listUserinfos as $userinfo) {
            $mailAddr = ($_ENV['ENV'] == 'PROD') ? $userinfo['mail'] : 'etienne.bohm@univ-paris1.fr';

            $headers = 'Content-Type: text/calendar; name="event.ics"; charset=utf-8' . "\r\n";
            $headers .= 'Content-Disposition: attachment; filename="event.ics"' . "\r\n";
            $headers .= 'Content-Transfer-Encoding: base64' . "\r\n";

            $envoiTest = mail($mailAddr, "Invitation à un evenement", base64_encode($icsData), $headers);

            if (!$envoiTest) {
                throw new Exception("erreur envoi mail");
            } else {
                $alertMailsEnvoyes[] = $userinfo['mail'];
            }
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
                                var jsduree=<?php echo (is_null($duree) ? 30:$duree); ?>;
                                var urlwsgroup='<?php echo $urlwsgroup; ?>';

                                <?php if (isset($duree) && !is_null($duree)) { ?>

                                $(function() {
                                    $('#duree option[value="<?php echo $duree ?>"').prop('selected', true);
                                });
                                <?php } ?>

                                <?php if ($uids && isset($js_uids)) { ?>
                                var jsuids=<?php echo "$js_uids" ?>;

                                $(function() {
                                    setOptionsUid(jsuids);

                                    if (jsuids.length < 2) {
                                        errorShow(true);
                                    }
                                });

                                <?php } ?>
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
                            <input type="submit" value="Trouver les créneaux" />
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
                                    <input type="checkbox" name="joursCreneaux[]" value="MO" <?php if(in_array('MO', $joursDemandes)) echo 'checked'?>>Lundi</input>
                                    <input type="checkbox" name="joursCreneaux[]" value="TU" <?php if(in_array('TU', $joursDemandes)) echo 'checked'?>>Mardi</input>
                                    <input type="checkbox" name="joursCreneaux[]" value="WE" <?php if(in_array('WE', $joursDemandes)) echo 'checked'?>>Mercredi</input>
                                    <input type="checkbox" name="joursCreneaux[]" value="TH" <?php if(in_array('TH', $joursDemandes)) echo 'checked'?>>Jeudi</input>
                                    <input type="checkbox" name="joursCreneaux[]" value="FR" <?php if(in_array('FR', $joursDemandes)) echo 'checked'?>>Vendredi</input>
                                </fieldset>
                                <br />
                            </div>
                            <div id="divplagehoraire">
                                <p>Plage horaire</p>
                                <div id="slider"></div>
                                <input type='hidden' name="plagesHoraires[]" value="<?php echo $plagesHoraires[0]; ?>" />
                                <input type='hidden' name="plagesHoraires[]" value="<?php echo $plagesHoraires[1]; ?>" />
                            </div>
                        </td>
                        <td>
                            <div id="divfromdate">
                                <p>A partir du</p>
                                <input type="date" name="fromDate" value="<?php echo $fromDate; ?>"/>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="creneauMailInput" tabindex="-1" aria-labelledby="modalInputLabel" aria-hidden="true" >
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
                                            <p>Description :</p>
                                            <input id="summarycreneau" type="text" disabled placeholdr="Description" name="summarycreneau" value="<?php echo $descriptionEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner une description pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')"/>
                                            <p>Lieu :</p>
                                            <input id="lieucreneau" type="text" disabled placeholder="Lieu de l'évenement" name="lieucreneau" value="<?php echo $lieuEvent; ?>" oninvalid="this.setCustomValidity('Veuillez renseigner un lieu pour l\'évenement')" onchange="if(this.value.length>0) this.setCustomValidity('')" />
                                    </div>
                                    <input type="datetime-local" disabled hidden="hidden" name="modalCreneauStart" />
                                    <input type="datetime-local" disabled hidden="hidden" name="modalCreneauEnd" />
                                </div>
                                <div class="modal-footer" id="creneauBoxFooter">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    <input type="submit" class="btn btn-primary" name="submitModal" value="Envoyer" />
                                </div>
                                <div class="mod"
                            </div>
                        </div>
                    </div>
            </table>
            </form>
        </div>

        <?php if (isset($listDate) && sizeof($listDate) > 0) { ?>
        <div id="reponse">
            <p>Créneaux disponibles</p>
            <ul>
                    <?php
                    $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd/MM/yyyy HH'h'mm");
                    $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm");

                    foreach ($listDate as $date) { ?>
                        <li>
                            <time><?php echo $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?></time>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#creneauMailInput" timeStart="<?php echo $date->startDate->getTimestamp() ?>" timeEnd="<?php echo $date->endDate->getTimestamp() ?>">Envoyer une invitation aux participants</a>
                        </li>
                        <?php } ?>
            </ul>
        </div>
        <?php } elseif (isset($listDate) && sizeof($listDate) == 0) { ?>
            <div>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
            </div>
        <?php } ?>

        <?php if ($actionFormulaireValider=='envoiInvitation' && isset($alertMailsEnvoyes) && sizeof($alertMailsEnvoyes) > 0) { ?>
            <script langage='javascript'>
            alert("Mails invitation envoyés à : <?php echo(implode(" - ", $alertMailsEnvoyes)) ?>");
            </script>
        <?php } ?>
    </body>
</html>