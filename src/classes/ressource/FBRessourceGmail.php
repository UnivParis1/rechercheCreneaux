<?php

declare(strict_types=1);

namespace RechercheCreneaux\ressource;

use League\Period\Sequence;
use RechercheCreneaux\FBRessource;
use RechercheCreneaux\FBParams;

/**
 * Classe servant à récupérer les free/busy venant de gmail
 */
class FBRessourceGmail extends FBRessource {

   private function __construct(String $dtz, String $url, FBParams $fbParams) {
        parent::__construct($url, $dtz, $fbParams);
        parent::setDateTimeZone($dtz);
    }

    public static function factory(String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams) : FBRessourceGmail {
        if (!isset(self::$duration)) {
            self::setDuration($dureeEnMinutes);
        }

        $finishedUrl = $url;
        $fbUser = new self($dtz, $finishedUrl, $fbParams);
        $fbUser->creneauxGenerated = $creneaux;

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

        return $fbUser;
    }

}
