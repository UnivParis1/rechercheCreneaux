<?php
use League\Period\Period as Period;

class FBInvite {
    var $listUserInfos;
    var $icsData;
    var $listDate;
    var $modalCreneauStart;
    var $modalCreneauEnd;
    var $sujet = "Invitation évenement";
    var $headers;
    var $varsHTTPGet;
    var $mailEffectivementEnvoye = false;
    var $mailEffectivementEnvoyeKey;


    public function __construct($uids, $urlwsgroup, $modalCreneauStart, $modalCreneauEnd, $titleEvent, $descriptionEvent, $lieuEvent, $dtz, $listDate, $varsHTTPGet) {
        $this->listDate = $listDate;
        $this->modalCreneauStart = $modalCreneauStart;
        $this->modalCreneauEnd = $modalCreneauEnd;
        $this->varsHTTPGet = $varsHTTPGet;

        $this->listUserInfos = FBUtils::requestUidsNames($uids, $urlwsgroup);
        $this->icsData = FBUtils::icalCreationInvitation($this->listUserInfos, $modalCreneauStart, $modalCreneauEnd, $titleEvent, $descriptionEvent, $lieuEvent, $dtz);

        $this->headers = 'Content-Type: text/calendar; name="event.ics"; charset=utf-8' . "\r\n";
        $this->headers .= 'Content-Disposition: attachment; filename="event.ics"' . "\r\n";
        $this->headers .= 'Content-Transfer-Encoding: base64' . "\r\n";
    }

    public function sendInvite() {
        if (!isset($_SESSION['inviteEnregistrement']))
            $_SESSION['inviteEnregistrement'] = array();

        foreach ($this->listUserInfos as $userinfo) {

            $idxListDate = FBUtils::getIdxCreneauxWithStartEnd($this->listDate, $this->modalCreneauStart, $this->modalCreneauEnd);

            if (($idxListDate >= 0) === false)
                throw new Exception("idxListDate error");

            $testInsertMail = false;
            if (!isset($_SESSION['inviteEnregistrement'][$idxListDate])) {
                $_SESSION['inviteEnregistrement'][$idxListDate] = array();
                $_SESSION['inviteEnregistrement'][$idxListDate]['modalCreneau'] = ['modalCreneauStart' => $this->modalCreneauStart, 'modalCreneauEnd' => $this->modalCreneauEnd];
                $_SESSION['inviteEnregistrement'][$idxListDate]['mails'] = [$userinfo['mail'] => false];
            } else {
                // test de vérification si il y'a eu envoi d'emails
                foreach ($_SESSION['inviteEnregistrement'][$idxListDate]['mails'] as $mail => $estEnvoye)
                    if ($mail == $userinfo['mail'] && $estEnvoye)
                        $testInsertMail = true;
            }

            $mailAddr = ($_ENV['ENV'] == 'PROD') ? $userinfo['mail'] : (($this->varsHTTPGet['debugmail']) ? $this->varsHTTPGet['debugmail'] : '');

            if (!$testInsertMail) {
                $envoiTest = mail($mailAddr, "Invitation à un evenement", base64_encode($this->icsData), $this->headers);

                if (!$envoiTest)
                    throw new Exception("erreur envoi mail");
                $this->mailEffectivementEnvoye = true;
                $this->mailEffectivementEnvoyeKey = $idxListDate;
                $_SESSION['inviteEnregistrement'][$idxListDate]['mails'][$userinfo['mail']] = true;
            }
        }
    }

    static public function invitationDejaEnvoyeSurCreneau(int $key, Period $dateAffichéeHTML) {
        if (array_key_exists('inviteEnregistrement', $_SESSION) === false)
            return false;

        $aaMEnvoyes = $_SESSION['inviteEnregistrement'];

        if (!(array_key_exists($key, $aaMEnvoyes) && array_key_exists('modalCreneau', $aaMEnvoyes[$key])))
            return false;

        $aMails = $aaMEnvoyes[$key];

        $modalCreneau = $aMails['modalCreneau'];
        $modalPeriod = Period::fromDate(new DateTime($modalCreneau['modalCreneauStart']), (new DateTime($modalCreneau['modalCreneauEnd'])));
        if (($modalPeriod <=> $dateAffichéeHTML) !== 0) {
            return false;
        }

        foreach ($aMails as $mail => $isSend)
            if ($isSend)
                return true;

        return false;
    }

    public function getMailsEnvoyes() {

        if (!$this->mailEffectivementEnvoye)
            return '';

        $key = $this->mailEffectivementEnvoyeKey;

        $alertMailsEnvoyes = $_SESSION['inviteEnregistrement'][$key];

        if (!$alertMailsEnvoyes)
            throw new Exception('erreur cle absente mails envoyés');

        $aMails = array();
        foreach($alertMailsEnvoyes['mails'] as $mail => $isSend)
            if ($isSend)
                $aMails[] = $mail;

        if (sizeof($aMails) > 0) {
            return implode(' - ', $aMails);
        }

        return '';
    }

    public function getMailEffectivementEnvoye() {
        return $this->mailEffectivementEnvoye;
    }
}
