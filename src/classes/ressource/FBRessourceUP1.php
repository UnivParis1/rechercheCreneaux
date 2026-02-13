<?php

declare(strict_types=1);

namespace RechercheCreneaux\ressource;

use stdClass;
use Exception;
use League\Period\Sequence;
use RechercheCreneaux\FBRessource;
use RechercheCreneaux\FBParams;
use RechercheCreneaux\Type\Userinfo;
use Kigkonsult\Icalcreator\Vcalendar;
use rfx\Type\Cast;

/**
 * Classe regroupant la gestion des users, fait l'appel au webservice agenda et normalise les créneaux busy
 * La normalisation vise à faire correspondre les créneaux busy avec les créneaux générés dans le but de faciliter les opérations de comparaisons
 * Ex: si un créneau busy est à cheval sur 2 créneaux, remplacer le busy par ces 2 créneaux et les considéré comme busy
 */
class FBRessourceUP1 extends FBRessource
{
    private ?Userinfo $uidInfos;

    /**
     * __construct
     *
     * @param string uid
     *
     * @return void
     */
    private function __construct(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams, bool $estOptionnel)
    {
        parent::__construct($uid, $dtz, $url, $dureeEnMinutes, $creneaux, $fbParams, $estOptionnel);
        parent::setDateTimeZone($dtz);

        $this->uidInfos = ($fbParams->stdEnv->wsgroup) ? self::_getUidInfos($uid, $fbParams->stdEnv) : null;
    }

    public static function factory(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams, bool $estOptionnel) : FBRessourceUP1 {
        $finishedUrl = $url . $uid;
        $fbUser = new self($uid, $dtz, $finishedUrl, $dureeEnMinutes, $creneaux, $fbParams, $estOptionnel);

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

        return $fbUser;
    }

    public function getUidInfos() : Userinfo {
        // ajout requête pour avoir mail et name sur api
        return $this->uidInfos;
    }

    public function getDisplayName() : string {

        return $this->uidInfos->displayName;
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

    public function _selectFreebusy(): void {

        $vcal = Vcalendar::factory()->parse($this->content);

        if ($vcal->countComponents() !== 1) {
            throw new \Exception("FBUser: component !== 1");
        }

        $component = $vcal->getComponent();
        $fbusys = $component->getAllFreebusy();

        $this->fbusys = $fbusys;
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
