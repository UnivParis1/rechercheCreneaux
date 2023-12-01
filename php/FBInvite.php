<?php
declare(strict_types=1);

use League\Period\Period as Period;

enum TypeInviteAction : int {
    case New = -1;
    case Exist = 0;
    case NewParticipants = 1;
}

class FBInvite {
    var $listUserInfos;
    var $icsData;
    var $listDate;
    var $modalCreneauStart;
    var $modalCreneauEnd;
    var $titleEvent;
    var $descriptionEvent;
    var $lieuEvent;
    var $sujet = "Invitation évenement";
    var $headers;
    var $varsHTTPGet;
    var $mailEffectivementEnvoye = false;
    var $mailEffectivementEnvoyeKey;
    var $mailEffectivementEnvoyeUids;

    public function __construct(array $fbUsers, $modalCreneauStart, $modalCreneauEnd, $titleEvent, $descriptionEvent, $lieuEvent, $dtz, $listDate, $varsHTTPGet) {
        $this->listDate = $listDate;
        $this->modalCreneauStart = $modalCreneauStart;
        $this->modalCreneauEnd = $modalCreneauEnd;
        $this->varsHTTPGet = $varsHTTPGet;
        $this->titleEvent = $titleEvent;
        $this->descriptionEvent = $descriptionEvent;
        $this->lieuEvent = $lieuEvent;

        // recupere les infos venant des $fbUsers et converti les stdObj en array
        $this->listUserInfos = $this->_getUserinfos($fbUsers);
        $this->icsData = FBUtils::icalCreationInvitation($this->listUserInfos, $modalCreneauStart, $modalCreneauEnd, $titleEvent, $descriptionEvent, $lieuEvent, $dtz);

        $this->headers = 'Content-Type: text/calendar; name="event.ics"; charset=utf-8' . "\r\n";
        $this->headers .= 'Content-Disposition: attachment; filename="event.ics"' . "\r\n";
        $this->headers .= 'Content-Transfer-Encoding: base64' . "\r\n";
    }

    private function _getUserinfos($fbUsers) {
        $arrayReturn = array();
        foreach ($fbUsers as $fbUser) {
            $stdInfos = $fbUser->getUidInfos();
            $arrayReturn[$stdInfos->uid] = get_object_vars($stdInfos);
        }
        return $arrayReturn;
    }

    public function sendInvite() {
        if (!isset($_SESSION['inviteEnregistrement']))
            $_SESSION['inviteEnregistrement'] = array();

        foreach ($this->listUserInfos as $uid => $userinfo) {
            $mailAddr = ($_ENV['ENV'] == 'PROD') ? $userinfo['mail'] : (($this->varsHTTPGet['debugmail']) ? $this->varsHTTPGet['debugmail'] : '');

            $idxSessionDate = FBUtils::getIdxCreneauxWithStartEnd($_SESSION['inviteEnregistrement'], new DateTime($this->modalCreneauStart), new DateTime($this->modalCreneauEnd));
            $idxSessionDate = ($idxSessionDate !== -1) ? $idxSessionDate: count($_SESSION['inviteEnregistrement']);

            $testInsertMail = false;
            if (!isset($_SESSION['inviteEnregistrement'][$idxSessionDate])) {
                $_SESSION['inviteEnregistrement'][$idxSessionDate] = array();
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['modalCreneau'] = ['modalCreneauStart' => $this->modalCreneauStart, 'modalCreneauEnd' => $this->modalCreneauEnd];
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['infos'] = ['titleEvent' => $this->titleEvent, 'descriptionEvent' => $this->descriptionEvent, 'lieuEvent' => $this->lieuEvent];
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'] = [$uid => array($userinfo['mail'], 'sended' => false) ];
            } else {
                // test de vérification si il y'a eu envoi d'emails, envoi si le mail est ajouté
                if (array_key_exists($uid, $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'])) {
                    foreach ($_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'] as $uid => $aMails) {
                        $mail = $aMails[0];
                        $estEnvoye = $aMails['sended'];
                        if ($mail == $userinfo['mail'] && $estEnvoye) {
                            $testInsertMail = true;
                            break;
                        }
                    }
                }
            }

            if (!$testInsertMail) {
                $envoiTest = mail($mailAddr, "Invitation à un evenement", base64_encode($this->icsData), $this->headers);

                if (!$envoiTest)
                    throw new Exception("erreur envoi mail");

                $this->mailEffectivementEnvoye = true;
                $this->mailEffectivementEnvoyeKey = $idxSessionDate;
                if (!isset($this->mailEffectivementEnvoyeUids))
                    $this->mailEffectivementEnvoyeUids = array();

                $this->mailEffectivementEnvoyeUids[] = $uid;
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'][$uid] = array($userinfo['mail'], 'sended' => true);
            }
        }
    }

    static public function invitationDejaEnvoyeSurCreneau(Period $dateAffichéeHTML, array $fbUsers) {
        if (array_key_exists('inviteEnregistrement', $_SESSION) === false)
            return TypeInviteAction::New;

        $aaMEnvoyes = &$_SESSION['inviteEnregistrement'];

        $key = FBUtils::getIdxCreneauxWithStartEnd($aaMEnvoyes, $dateAffichéeHTML->startDate, $dateAffichéeHTML->endDate);

        if (!($key != -1 && array_key_exists($key, $aaMEnvoyes) && array_key_exists('modalCreneau', $aaMEnvoyes[$key])))
            return TypeInviteAction::New;

        $aInfos = &$aaMEnvoyes[$key];

        $modalCreneau = $aInfos['modalCreneau'];
        $modalPeriod = Period::fromDate(new DateTime($modalCreneau['modalCreneauStart']), (new DateTime($modalCreneau['modalCreneauEnd'])));
        if (($modalPeriod <=> $dateAffichéeHTML) !== 0) {
            return TypeInviteAction::New;
        }

// Test si le nombre de users est différent sur la session
        if (count($fbUsers) > count($aInfos['mails']))
            return TypeInviteAction::NewParticipants;

        foreach ($aInfos['mails'] as $aMails) {
            if ($aMails['sended'])
                return TypeInviteAction::Exist;
        }

        return TypeInviteAction::New;
    }

    public function getMailsEnvoyes() {

        if (!$this->mailEffectivementEnvoye)
            return '';

        $key = $this->mailEffectivementEnvoyeKey;
        $uids = $this->mailEffectivementEnvoyeUids;

        $alertMailsEnvoyes = $_SESSION['inviteEnregistrement'][$key];

        if (!$alertMailsEnvoyes)
            throw new Exception('erreur cle absente mails envoyés');

        $rMails = array();

        foreach ($uids as $uid) {
            $aMails = $alertMailsEnvoyes['mails'][$uid];
            if ($aMails['sended'])
                $rMails[] = $aMails[0];
        }

        if (sizeof($rMails) > 0) {
            return implode(' - ', $rMails);
        }

        return '';
    }

    public function getMailEffectivementEnvoye() {
        return $this->mailEffectivementEnvoye;
    }
}
