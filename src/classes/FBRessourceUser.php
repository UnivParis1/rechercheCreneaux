<?php

declare(strict_types=1);

namespace RechercheCreneaux;

use stdClass;
use Exception;
use League\Period\Sequence;
use RechercheCreneaux\FBRessource;
use RechercheCreneaux\Type\Userinfo;
use rfx\Type\Cast;

/**
 * Classe regroupant la gestion des users, fait l'appel au webservice agenda et normalise les créneaux busy
 * La normalisation vise à faire correspondre les créneaux busy avec les créneaux générés dans le but de faciliter les opérations de comparaisons
 * Ex: si un créneau busy est à cheval sur 2 créneaux, remplacer le busy par ces 2 créneaux et les considéré comme busy
 */
class FBRessourceUser extends FBRessource {

    /**
     * @var string uid
     */
    public string $uid;


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
    private function __construct(String $uid, String $dtz, String $url, bool $estOptionnel, FBParams $fbParams) {

        parent::__construct($url, $dtz, $fbParams);
        parent::setDateTimeZone($dtz);

        $this->uid = $uid;
        $this->estOptionnel = $estOptionnel;

        $this->uidInfos = ($fbParams->stdEnv->wsgroup) ? self::_getUidInfos($uid, $fbParams->stdEnv) : null;
    }

    public static function factory(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, bool $estOptionnel, FBParams $fbParams) : FBRessourceUser {
        if (!isset(self::$duration)) {
            self::setDuration($dureeEnMinutes);
        }

        $finishedUrl = $url . $uid;
        $fbUser = new self($uid, $dtz, $finishedUrl, $estOptionnel, $fbParams);
        $fbUser->creneauxGenerated = $creneaux;

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

        return $fbUser;
    }


    private function _testSiAgendaBloque(Sequence &$busySeq) : bool {

        $testFBUserclone = clone($this);
        $seqToTest = clone($busySeq);

        // generation de créneaux standards
        $fbParamsClone = clone($this->fbParams);
        $fbParamsClone->fromDate = date('Y-m-d');
        $fbParamsClone->duree = 60;
        $fbParamsClone->plagesHoraires = array('9-12', '14-17');
        $fbParamsClone->joursDemandes = ['MO', 'TU', 'WE', 'TH', 'FR'];

        $creneauxGeneratedTest = (new FBCreneauxGeneres($fbParamsClone))->getCreneauxSeq();

        $testFBUserclone->setCreneauxGenerated($creneauxGeneratedTest);
        $seq = $testFBUserclone->_instanceCreneaux($seqToTest);
        $testFBUserclone->setSequence($seq);

        $fbUsersTest = array($testFBUserclone);
        $fbCompareTest = new FBCompare($fbUsersTest, $creneauxGeneratedTest, $this->dateTimeZone->getName(), 1);

        $testCompare = $fbCompareTest->getNbResultatsAffichés();

        if ($testCompare == 0) {
            $this->estFullBloquer = true;
            return true;
        }
        return false;
    }

    public function getEstOptionnel() {
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
