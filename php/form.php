<?php

require 'vendor/autoload.php';

require_once('FBUser.php');
require_once('FBUtils.php');
require_once('FBCompare.php');
require_once('FBCreneauxGeneres.php');

setlocale(LC_TIME, "fr_FR");

date_default_timezone_set('Europe/Paris');
$dtz = date_default_timezone_get();
$url = 'https://echange.univ-paris1.fr/kronolith/fb.php?u=';

$vars = filter_var_array($_GET);

$uids = isset($_GET['listuids']) ? $_GET['listuids'] : null;
$nbcreneaux = isset($_GET['creneaux']) ? $_GET['creneaux'] : null;
$duree = isset($_GET['duree']) ? $_GET['duree'] : null;
$plagesHoraires = isset($_GET['plagesHoraires']) ? $_GET['plagesHoraires'] : array('9-12', '14-17');

if (($uids && sizeof($uids) > 1) && ($plagesHoraires && sizeof($plagesHoraires) > 0) && $nbcreneaux && $duree) {
    $js_uids = json_encode($uids);

    $creneauxGenerated = (new FBCreneauxGeneres($duree, $plagesHoraires, $dtz))->getCreneauxSeq();

    $fbUsers = array();
    foreach ($uids as $uid) {
        $fbUsers[] = FBUser::factory($uid, $dtz, $url, $duree, $creneauxGenerated);
    //    FBUtils::drawSequence($fbUser->getSequence()->jsonSerialize());
    }
    $creneauxFinauxList = (new FBCompare($fbUsers, $creneauxGenerated))->substractBusysFromCreneaux()->toList();
    $sizeFinal = sizeof($creneauxFinauxList);
    $nbDisplay = ($nbcreneaux > $sizeFinal) ? $sizeFinal : $nbcreneaux;

    $listDate = array();
    for ($i = 0; $i < $nbDisplay; $i++) {
        $listDate[] = $creneauxFinauxList[$i];
    }
}
?>

<!DOCTYPE html>
    <head>
        <style>
            .alertrequire {
                color: red;
                display: none;
            }
        </style>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous" />
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>

        <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
        <script type='text/javascript' src="https://wsgroups.univ-paris1.fr/web-widget/autocompleteUser-resources.html.js"></script>
        <script type='text/javascript' src='./form.js'></script>
    </head>
    <body>
        <h1>Recherche de disponibilités</h1>
        <form id="form" action="">
                <table>
                <tr>
                    <td>
                        <p>Séléction des Users</p>
                        <input id="person" name="person" placeholder="Nom et/ou prenom" />
                        
                        <script>
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
                        <input id="creneaux" name="creneaux" type="number" value="3" />
                    </td>
                    <td>
                        <p>Durée des créneaux (minutes)</p>
                        <input id="duree" name="duree" type="number" value="<?php print($duree ? $duree : 30) ?>" />
                    </td>
                    <td>
                        <p>Envoyer requête</p>
                        <input type="submit" />
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
                </tr>
                </table>
        </form>


        <?php if (isset($listDate) && sizeof($listDate) > 0) { ?>
        <div>
            <p>Créneaux disponibles</p>
            <ul>
                    <?php
                    $formatter_start = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd/MM/yyyy HH'h'mm");
                    $formatter_end = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "HH'h'mm");

                    /* var $date League\Period\Period */
                    foreach ($listDate as $date) {
                         //$date = new League\Period\Period();
                        ?>
                        <li>
                            <time><?php echo $formatter_start->format($date->startDate->getTimestamp()) . ' - ' . $formatter_end->format($date->endDate->getTimestamp()) ?></time>
                        </li>
                        <?php } ?>
            </ul>
        </div>
        <?php } elseif (isset($listDate) && sizeof($listDate) == 0) { ?>
            <div>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
            </div>
        <?php } ?>
    </body>
</html>

