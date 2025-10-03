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
            if (self::compareCreneaux(creneaux: $creneaux, propositions: $eventTested['questions'][0]['propositions'])) {
                $event = $eventTested;
                break;
            }
        }

        if ($event == null) {
            return;
        }

        // verifier les users si au moins 1 Fbusers[] users est présents dans les events
        if (!self::usersExistantEvent($fbUsers,$event)) {
            $this->event = [];
            return;
        }

        ($this->titreEvento != null) ?: $this->titreEvento = $event['title'];
        ($this->summaryevento != null) ?: $this->summaryevento = $event['description'];

        // tout est bon, event trouvé en session
        $this->donneeExistante = true;
        $this->event = $event;
    }

    private static function usersExistantEvent(array $fbUsers, $event):bool {
        foreach ($fbUsers as $fbUser) {
            foreach ($event['new_guests'] as $mail) {
                if ($mail == $fbUser->getUidInfos()->mail) {
                   return true;
                }
            }
        }
        return false;
    }

    private static function compareCreneaux($creneaux, $propositions): bool {
        $test = false;
        foreach ($propositions as $proposition) {
            foreach ($creneaux as $creneau) {
                $start = $creneau->startDate->getTimestamp();
                $end = $creneau->endDate->getTimestamp();

                $local_base_day = $proposition['local_base_day'];
                $base_time = $proposition['base_time'];
                $end_time = $proposition['end_time'];

                if ($local_base_day+$base_time == $start && $local_base_day+$end_time == $end) {
                    $test = true;
                    break;
                 } else {
                    $test = false;
                 }
            }
        }
        return $test;
    }
}
