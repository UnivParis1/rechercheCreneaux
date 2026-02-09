<?php

declare(strict_types=1);

namespace RechercheCreneaux\ressource;

use stdClass;
use Exception;
use League\Period\Sequence;
use RechercheCreneaux\FBRessource;
use RechercheCreneaux\FBParams;
use RechercheCreneaux\FBCreneauxGeneres;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\Type\Userinfo;
use rfx\Type\Cast;

/**
 * Classe regroupant la gestion des users, fait l'appel au webservice agenda et normalise les créneaux busy
 * La normalisation vise à faire correspondre les créneaux busy avec les créneaux générés dans le but de faciliter les opérations de comparaisons
 * Ex: si un créneau busy est à cheval sur 2 créneaux, remplacer le busy par ces 2 créneaux et les considéré comme busy
 */
class FBRessourceUser extends FBRessource
{
   /** @var bool $estDisqualifier
     * si son agenda n'est pas pris en compte dans les résultats
     * dans le cas où la recherche donne 0 résultats, on élimine les agendas les
     * plus chargés
    */
    public bool $estDisqualifier = false;


    public bool $estOptionnel;

    private ?Userinfo $uidInfos;

    /**
     * __construct
     *
     * @param string uid
     *
     * @return void
     */
    private function __construct(String $uid, String $dtz, String $url, int $dureeEnMinutes, bool $estOptionnel, Sequence &$creneaux, FBParams $fbParams)
    {
        parent::__construct($uid, $dtz, $url, $dureeEnMinutes, $creneaux, $fbParams);
        parent::setDateTimeZone($dtz);

        $this->estOptionnel = $estOptionnel;

        $this->uidInfos = ($fbParams->stdEnv->wsgroup) ? self::_getUidInfos($uid, $fbParams->stdEnv) : null;
    }

    public static function factory(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, bool $estOptionnel, FBParams $fbParams) : FBRessourceUser {
        $finishedUrl = $url . $uid;
        $fbUser = new self($uid, $dtz, $finishedUrl, $dureeEnMinutes, $estOptionnel, $creneaux, $fbParams);

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

        return $fbUser;
    }



    public function getEstOptionnel(): bool {
        return $this->estOptionnel;
    }

    public function getUidInfos() : Userinfo {
        // ajout requête pour avoir mail et name sur api
        return $this->uidInfos;
    }

    /**
     * _getUidInfos
     *
     * @param  string $uid
     * @param  stdClass $stdEnv
     * @return Userinfo
     */
    public static function _getUidInfos(string $uid, $stdEnv): Userinfo  {
        $infos = self::requestUidInfo($uid, $stdEnv->urlwsgroupUserInfos);

        if ($infos === null)
            throw new Exception("_gellFullnameWithUid erreur récupération uid: $uid");

        return $infos;
    }

    /**
     * Appel au webservice pour obtenir des informations supplémentaires sur un utilisateur
     *
     * @param  string $uid
     * @param  string $urlwsgroup
     * @return Userinfo;
     */
    private static function requestUidInfo(string $uid, string $urlwsgroup) : Userinfo {
        $url = "$urlwsgroup?token=$uid&maxRows=1&attrs=uid,displayName,mail";

        $fd = fopen($url, 'r');
        $ajaxReturn = stream_get_contents($fd);
        fclose($fd);

        $arrayReturn = json_decode($ajaxReturn);

        $exMsg = "Erreur fonction requestUidInfo";
        if ($ajaxReturn[0]) {
            foreach ($arrayReturn as $stdObj) {
                if ($stdObj->uid == $uid) {
                    return Cast::as($stdObj, Userinfo::class);
                }
            }
        }

        error_log("requestUidInfo url : " . var_export($url, true));
        error_log("requestUidInfo ajaxReturn : " . var_export($ajaxReturn, true));

        throw new Exception($exMsg);
    }
}
