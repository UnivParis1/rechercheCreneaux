<?php

require 'vendor/autoload.php';
require 'FBUtils.php';
require 'FBUser.php';
require 'FBCompare.php';

$uids = isset($_GET['listuids']) ? $_GET['listuids'] : null;
$creneaux = isset($_GET['creneaux']) ? $_GET['creneaux'] : null;
$duree = isset($_GET['duree']) ? $_GET['duree'] : null;

if ($uids && sizeof($uids) > 1 && $creneaux && $duree) {
    $js_uids = json_encode($uids);

    $url = "https://echange.univ-paris1.fr/kronolith/fb.php?u=";
    FBUser::setDuration($duree);
    FBUser::setUrl($url);

    $fbUsers = array();
    foreach ($uids as $uid) {
        $fbUser = FBUser::factory($uid);
        $fbUsers[] = $fbUser;
    }

    $fbCompare = new FBCompare($fbUsers);
    $periods = $fbCompare->compareSequences();

    if (sizeof($periods) > 0) {
        $listDate = array();

        for ($i = 0; $i < $creneaux; $i++) {
            if ($np = next($periods))
                $listDate[] = $np->startDate->format('m.d.y H\Hi');
        }
    }
//echo "<pre>";
//    die(var_dump($periods));
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
        <form id="form" action="">
            <div>
                <table>
                <tr>
                    <td>
                        <p>Recherche de l'utisateur</p>
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
                            <p>Séléction des Users<br />(uid) suivants</p>
                            <p class="alertrequire">Séléction minimum de 2 utilisateurs</p>
                            <ul id="person_ul">
                            </ul>
                        </div>
                    </td>
                </tr>
                </table>
            </div>
        </form>


        <?php if (isset($listDate)) { ?>
        <div>
            <p>Résultats</p>
            <ul>
                    <?php foreach ($listDate as $date) { ?>
                        <li>
                            <time><?php echo $date; ?></time>
                        </li>
                        <?php } ?>
            </ul>
        </div>
        <?php } elseif (sizeof($periods) == 0) { ?>
            <div>
            <p>Aucun créneaux commun disponible pour ces utilisateurs</p>
            </div>
        <?php } ?>
    </body>
</html>
