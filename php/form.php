<?php
$uid = isset($_GET['person']) ? $_GET['person'] : null;
$uids = isset($_GET['listuids']) ? $_GET['listuids'] : null;
$creneaux = isset($_GET['creneaux']) ? $_GET['creneaux'] : null;

if ($uids && $creneaux) {
    $js_uids = json_encode($uids);
    
    $listDate = array();

    for ($i = 0; $i < 3; $i++) {
        $listDate[] = date('m.d.y H') . 'H';
    }
}
?>

<html>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
    </head>
    <body>
        <form action="">
            <div>
                <table>
                <tr>
                    <td>                        
                        <p>Uid de l'utisateur</p>
                        <script src="https://wsgroups.univ-paris1.fr/web-widget/autocompleteUser-resources.html.js"></script>
                        <input id="person" name="person" placeholder="Nom et/ou prenom" />
                        
                        <script>
                            
                            <?php if ($uids && isset($js_uids)) { ?>
                            var jsuids=<?php echo "$js_uids" ?>;
                            
                            $(function() {
                                setOptionsUid(jsuids);
                            });
                            
                            <?php } ?>
                            
                            var urlwsgroup = 'https://wsgroups.univ-paris1.fr/searchUser';
                            
                            var idpersonselect = "#personselect";
                            var divpersonselect = "#divpersonselect";
                            
                            function getCurrentOptions() {
                                var getVals = new Array();
                                $(idpersonselect + " option:selected").each(function(idx, option) {
                                    getVals[idx] = option.value;
                                });
                                
                                return getVals;
                            }
                            
                            function setOptionsUid(jsuids) {
                                for (uid of jsuids) {
                                    addOptionWithUid(uid);
                                }
                            }
                            
                            function addOptionWithUid(uid) {                                
                                var newOption = $('<option>');
                                newOption.attr('value',uid).attr('selected', '').text(uid);
                                $(idpersonselect).append(newOption);
                                $(divpersonselect).show();
                            }                            
                            
                            function addOptionUid() {
                                var uid=this.value;                                
                                
                                if (getCurrentOptions().indexOf(uid) == -1) {
                                    addOptionWithUid(uid);                                    
                                }
                            }
                            
                            $("#person").autocompleteUser(
                                    urlwsgroup, {
                                    select: addOptionUid
                                    }
                            );
                    
                        </script>
                    </td>
                    <td>
                        <p>Nombre de créneaux</p>
                        <input id="creneaux" name="creneaux" type="number" value="3" />
                    </td>
                    <td>
                        <p>Envoyer requête</p>
                        <input type="submit" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <div id="divpersonselect" hidden>
                            <br />
                            <p>Séléction des Users<br />(uid) suivants</p>
                            <select id="personselect" multiple="multiple" name="listuids[]">
                                <optgroup id="personselectopt" label="Uid" />
                            </select>
                        </div>
                    </td>
                </tr>
                </table>
            </div>
        </form>


        <div>
            <p>Résultats</p>
            <ul>
                <?php if (isset($listDate)) { ?>
                    <?php foreach ($listDate as $date) { ?>
                        <li>
                            <time><?php echo $date; ?></time>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
        </div>
    </body>
</html>
