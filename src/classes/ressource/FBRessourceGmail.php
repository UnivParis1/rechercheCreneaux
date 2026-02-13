<?php

declare(strict_types=1);

namespace RechercheCreneaux\ressource;

use League\Period\Sequence;
use RechercheCreneaux\FBRessource;
use RechercheCreneaux\FBParams;
use Kigkonsult\Icalcreator\Vcalendar;

/**
 * Classe servant à récupérer les free/busy venant de gmail
 */
class FBRessourceGmail extends FBRessource {

   private function __construct(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams, bool $estOptionnel = false) {
        parent::__construct($uid, $dtz, $url, $dureeEnMinutes, $creneaux, $fbParams, $estOptionnel);
        parent::setDateTimeZone($dtz);
    }

    public static function factory(String $uid, String $dtz, String $url, int $dureeEnMinutes, Sequence &$creneaux, FBParams $fbParams, bool $estOptionnel = false) : FBRessourceGmail {
        $fbUser = new self($uid, $dtz, $url, $dureeEnMinutes, $creneaux, $fbParams, $estOptionnel);

        $fbUser->_selectFreebusy();
        $busySeq = $fbUser->_initSequence();

        if ($fbUser->_testSiAgendaBloque($busySeq)) {
            $fbUser->estFullBloquer = true;
        }

        $fbUser->sequence = $fbUser->_instanceCreneaux($busySeq);

        return $fbUser;
    }

    public function _selectFreebusy(): void
    {
        $vcal = Vcalendar::factory()->parse($this->content);

        $components = $vcal->getComponents('Vevent');

        $fbusys = [];

        $i = 0;
        foreach ($components as $event) {
            $summary = $event->getSummary();

            if ($summary !== 'Busy') {
                $i++;
                continue;
            }

            $dtstart = $event->getDtstart();
            $dtend = $event->getDtend();

            // index 0 à mettre pour conformité avec la méthode getAllFreebusy de la librairie kigkonsult::icalcreator. Méthode appelée précédemment pour calendriers up1
            $fbusys[$i] = [0];
            $fbusys[$i][0] = [0 => $dtstart, 1 => $dtend];
            $i++;
        }
        $this->fbusys = $fbusys;
    }
}
