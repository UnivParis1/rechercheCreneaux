<?php
declare(strict_types=1);

namespace RechercheCreneaux;

class FBEventoSession {
    public  bool   $donneeExistante = false;
    public  array  $event = [];

    public ?string $titreEvento;
    public ?string $summaryevento;

    private string $uidUser;

    public function __construct(string $uidUser, FBParams $fBParams, array $fbUsers, array $creneaux) {
        // si pas de données en session $donneeExistance reste à false
        if (! (isset($_SESSION['evento'][$uidUser]) && count($_SESSION['evento'][$uidUser]) > 0))
            return;

        $this->titreEvento = ($fBParams->titreEvento != null) ? $fBParams->titreEvento : null;
        $this->summaryevento = ($fBParams->summaryevento != null) ? $fBParams->summaryevento: null;

        $events = $_SESSION['evento'][$uidUser];

        $event = null;
        foreach ($events as $eventTested) {
            if (self::compareCreneaux($creneaux, $eventTested['questions'][0]['propositions'])) {
                $event = $eventTested;
                break;
            }
        }

        if ($event == null) {
            return;
        }

        // verifier les users si tous les events users sont présents dans Fbusers[]
        if (!self::usersExistantEvent($event, $fbUsers)) {
            $this->event = [];
            return;
        }

        ($this->titreEvento != null) ?: $this->titreEvento = $event['title'];
        ($this->summaryevento != null) ?: $this->summaryevento = $event['description'];

        // tout est bon, event trouvé en session
        $this->donneeExistante = true;
        $this->event = $event;
    }

    private static function usersExistantEvent($event, array $fbUsers) {
        foreach ($event['new_guests'] as $mail) {
            $test = false;
            foreach ($fbUsers as $fbUser) {
                if ($mail == $fbUser->getUidInfos()->mail) {
                   $test = true;
                   break;
                }
            }
            if ($test == false) {
                return false;
            }
        }

        return true;
    }

    private static function compareCreneaux($creneaux, $propositions) {
        foreach ($propositions as $proposition) {
            foreach ($creneaux as $creneau) {
                $start = $creneau->startDate->getTimestamp();
                $end = $creneau->endDate->getTimestamp();

                $base_day = $proposition['base_day'];
                $base_time = $proposition['base_time'];
                $end_time = $proposition['end_time'];

                if ($base_day+$base_time-$start == $base_day+$end_time-$end)
                    continue;
                else
                    return false;
            }
        }
        return true;
    }


}
