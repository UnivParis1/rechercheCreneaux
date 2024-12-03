<?php
declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;
use Exception;
use IntlDateFormatter;
use RechercheCreneaux\FBUtils;
use League\Period\Period as Period;
use RechercheCreneauxLib\EasyPeasyICSUP1;
use PHPMailer\PHPMailer\PHPMailer;

enum TypeInviteAction : int {
    case New = -1;
    case Exist = 0;
    case NewParticipants = 1;
}

/**
 * Gère les invitations agenda ICS envoyés aux participants
 */
class FBInvite {
    var FBForm $fbForm;
    var array $fbUsers;
    var $listUserInfos;
    var $listDate;
    var $modalCreneauStart;
    var $modalCreneauEnd;
    var $titleEvent;
    var $descriptionEvent;
    var $lieuEvent;
    var $dtz;
    var $sujet = "Invitation évenement";

    var $stdEnv;
    var $mailEffectivementEnvoye = false;
    var $mailEffectivementEnvoyeKey;
    var $mailEffectivementEnvoyeUids;
    var ?string $from;
    private stdClass $organisateur;

    public function __construct($fbForm, $fbParams, $stdEnv, $listDate) {
        $this->fbForm = $fbForm;
        $this->fbUsers = $fbForm->getFbUsers();

        $this->listDate = $listDate;
        $this->modalCreneauStart = $fbParams->modalCreneauStart;
        $this->modalCreneauEnd = $fbParams->modalCreneauEnd;
        $this->stdEnv = $stdEnv;
        $this->dtz = $stdEnv->dtz;
        $this->titleEvent = $fbParams->titleEvent;
        $this->descriptionEvent = $fbParams->descriptionEvent;
        $this->lieuEvent = $fbParams->lieuEvent;

        // recupere les infos venant des $fbUsers et converti les stdObj en array
        $this->listUserInfos = $this->_getUserinfos($this->fbUsers);

        if (isset($stdEnv->uidCasUser)) {
            $this->organisateur = FBUtils::requestUidInfo($stdEnv->uidCasUser, $stdEnv->urlwsgroupUserInfos);
        } else {
            $this->organisateur = $this->fbUsers[0]->getUidInfos();
        }

        // ajout du from spécifié dans .env dans les headers si besoin en local
        $this->from = $stdEnv->mailfrom ?? null;
    }

    public static function verifSiInvitation($fbParams) {
        if ($fbParams->actionFormulaireValider == 'envoiInvitation' && is_null($fbParams->titleEvent) == false && is_null($fbParams->descriptionEvent) == false) {
            return true;
        }
        return false;
    }

    private function _genereStdCourriel($userinfo, $stdEventInfos ): stdClass {

        $formatter_day = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd/MM/yyyy à HH'h'mm");
        $dateFormatLongFR = $formatter_day->format((new DateTime($this->modalCreneauStart))->getTimestamp());

        $subject = "Réunion {$this->titleEvent}, le $dateFormatLongFR";

        $texte = "Bonjour {$userinfo->displayName},

{$this->organisateur->displayName} vous invite à participer à l'événement suivant:

« {$this->titleEvent} », le $dateFormatLongFR

Description de l'événement :
« {$this->descriptionEvent} »

Lieu :
« {$this->lieuEvent} »

Pour accepter l'événement :
https://echange.univ-paris1.fr/kronolith/attend.php?c=$stdEventInfos->calendarID&e=$stdEventInfos->eventID&u=$userinfo->mail&a=accept

Pour accepter l'événement à titre provisoire :
https://echange.univ-paris1.fr/kronolith/attend.php?c=$stdEventInfos->calendarID&e=$stdEventInfos->eventID&u=$userinfo->mail&a=tentative

Pour décliner l'événement :
https://echange.univ-paris1.fr/kronolith/attend.php?c=$stdEventInfos->calendarID&e=$stdEventInfos->eventID&u=$userinfo->mail&a=decline


Cordialement,

{$this->organisateur->displayName}
";

        $stdObj = new stdClass();
        $stdObj->subject = $subject;
        $stdObj->corps = $texte;

        return $stdObj;
    }

    private function _genereICS($userinfo) : string {
        $userinfo = (object) $userinfo;

        $eICS = new EasyPeasyICSUP1($this->organisateur->displayName);

        $dataics =['start' => (new DateTime($this->modalCreneauStart))->getTimestamp(),
                    'end' => (new DateTime($this->modalCreneauEnd))->getTimestamp(),
                    'summary' => $this->titleEvent,
                    'description' => $this->descriptionEvent,
                    'organizer' => $this->organisateur->displayName,
                    'organizer_email' => $this->organisateur->mail,
                    'location' => $this->lieuEvent ];

        foreach ($this->listUserInfos as $uid2 => $userinfo2) {
            $userinfo2 = (object) $userinfo2;
            $dataics['guests'][] = ['name' => $userinfo2->displayName, 'email' => $userinfo2->mail];
        }

        $eICS->addEvent($dataics);

        return $eICS->render(false);
    }

    private function _getUserinfos($fbUsers) {
        $arrayReturn = [];
        foreach ($fbUsers as $fbUser) {
            $stdInfos = $fbUser->getUidInfos();
            $arrayReturn[$stdInfos->uid] = get_object_vars($stdInfos);
        }
        return $arrayReturn;
    }

    public function sendInvite(): void {
        if (!isset($_SESSION['inviteEnregistrement']))
            $_SESSION['inviteEnregistrement'] = [];

        // envoi à l'organisateur
        if ( ! $stdEvent = $this->sendICSKronolith())
            throw new Exception("erreur communication ICS serveur");

        foreach ($this->listUserInfos as $uid => $userinfo) {
            $idxSessionDate = FBUtils::getIdxCreneauxWithStartEnd($_SESSION['inviteEnregistrement'], new DateTime($this->modalCreneauStart), new DateTime($this->modalCreneauEnd));
            $idxSessionDate = ($idxSessionDate !== -1) ? $idxSessionDate: count($_SESSION['inviteEnregistrement']);

            $testInsertMail = false;
            if (!isset($_SESSION['inviteEnregistrement'][$idxSessionDate])) {
                $_SESSION['inviteEnregistrement'][$idxSessionDate] = [];
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['modalCreneau'] = ['modalCreneauStart' => $this->modalCreneauStart, 'modalCreneauEnd' => $this->modalCreneauEnd];
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['infos'] = ['titleEvent' => $this->titleEvent, 'descriptionEvent' => $this->descriptionEvent, 'lieuEvent' => $this->lieuEvent];
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'] = [$uid => [$userinfo['mail'], 'sended' => false, $userinfo] ];
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
                $userinfo = (object) $userinfo;
                $stdDataMail = $this->_genereStdCourriel($userinfo, $stdEvent);

                $phpmailer = new PHPMailer(false);
                $phpmailer->CharSet = 'UTF-8';

                $phpmailer->isSendmail();

                if ($this->from)
                    $phpmailer->setFrom($this->from);

                $phpmailer->addAddress($this->stdEnv->env == 'prod' ? $userinfo->mail : $this->organisateur->mail, $userinfo->displayName);

                $phpmailer->Subject = $stdDataMail->subject;
                $phpmailer->Body = $stdDataMail->corps;

                if ( ! $phpmailer->send())
                    throw new Exception("Erreur envoi mail FBInvitation pour : $userinfo->mail");

                $this->mailEffectivementEnvoye = true;
                $this->mailEffectivementEnvoyeKey = $idxSessionDate;
                if (!isset($this->mailEffectivementEnvoyeUids))
                    $this->mailEffectivementEnvoyeUids = [];

                $this->mailEffectivementEnvoyeUids[] = $uid;
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'][$uid] =  [$userinfo['mail'], 'sended' => true, $userinfo];
            }
        }
    }

    private function sendICSKronolith(): bool|stdClass {

        $ch = curl_init($this->stdEnv->kronolith_import_url_user . '?user='. $this->organisateur->uid);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_genereICS($this->organisateur));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/calendar']);
        $response = curl_exec($ch);
        curl_close($ch);

        if ( ! $eventStd = json_decode($response))
            return false;

        return $eventStd;
    }


    /**
     * invitationDejaEnvoyeSurCreneau
     *
     * @param  Period $dateAffichéeHTML
     * @param  array $fbUsers
     * @return stdClass
     */
    static public function invitationDejaEnvoyeSurCreneau(Period $dateAffichéeHTML, array $fbUsers) : stdClass {
        $returnStd = new stdClass();

        if (array_key_exists('inviteEnregistrement', $_SESSION) === false) {
            $returnStd->typeInvationAction = TypeInviteAction::New;
            return $returnStd;
        }

        $aaMEnvoyes = &$_SESSION['inviteEnregistrement'];

        $key = FBUtils::getIdxCreneauxWithStartEnd($aaMEnvoyes, $dateAffichéeHTML->startDate, $dateAffichéeHTML->endDate);

        if (!($key != -1 && array_key_exists($key, $aaMEnvoyes) && array_key_exists('modalCreneau', $aaMEnvoyes[$key]))) {
            $returnStd->typeInvationAction = TypeInviteAction::New;
            return $returnStd;
        }

        $aInfos = &$aaMEnvoyes[$key];

        $modalCreneau = $aInfos['modalCreneau'];
        $modalPeriod = Period::fromDate(new DateTime($modalCreneau['modalCreneauStart']), (new DateTime($modalCreneau['modalCreneauEnd'])));
        if (($modalPeriod <=> $dateAffichéeHTML) !== 0) {
            $returnStd->typeInvationAction = TypeInviteAction::New;
            return $returnStd;
        }

// Test si le nombre de users est différent sur la session
        if (count($fbUsers) > count($aInfos['mails'])) {
            $returnStd->mails = FBUtils::getMailsSended($aInfos['mails']);
            $returnStd->typeInvationAction = TypeInviteAction::NewParticipants;
            return $returnStd;
        }

        foreach ($aInfos['mails'] as $aMails) {
            if ($aMails['sended']) {
                $returnStd->mails = $aInfos['mails'];
                $returnStd->typeInvationAction = TypeInviteAction::Exist;
                return $returnStd;
            }
        }

        $returnStd->typeInvationAction = TypeInviteAction::New;
        return $returnStd;
    }

    public function getMailsEnvoyes() {

        if (!$this->mailEffectivementEnvoye)
            return '';

        $key = $this->mailEffectivementEnvoyeKey;
        $uids = $this->mailEffectivementEnvoyeUids;

        $alertMailsEnvoyes = $_SESSION['inviteEnregistrement'][$key];

        if (!$alertMailsEnvoyes)
            throw new Exception('erreur cle absente mails envoyés');

        $rMails = $this->__rMailSended($uids, $alertMailsEnvoyes);

        if (sizeof($rMails) > 0) {
            return implode(' - ', $rMails);
        }

        return '';
    }

    // fonction retournant les mails envoyé avec un test si effectivement bien envoyé
    private function __rMailSended($uids, $alertMailsEnvoyes) {
        $rMails = array();

        foreach ($uids as $uid) {
            $aMails = $alertMailsEnvoyes['mails'][$uid];
            if ($aMails['sended'])
                $rMails[] = $aMails[0];
        }
        return $rMails;
    }

    public function getMailEffectivementEnvoye() {
        return $this->mailEffectivementEnvoye;
    }
}
