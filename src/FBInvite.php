<?php
declare(strict_types=1);

namespace RechercheCreneaux;

use DateTime;
use stdClass;
use Exception;
use IntlDateFormatter;
use RechercheCreneaux\FBUtils;
use League\Period\Period as Period;
use PHPMailer\PHPMailer\PHPMailer;
use RechercheCreneauxLib\EasyPeasyICSUP1;

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
    var string $from;
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
            $this->from = $this->organisateur->mail;
        } else {
            $this->organisateur = $this->fbUsers[0]->getUidInfos();
            $this->from = $this->organisateur->mail;
        }
        if ($stdEnv->env == 'local') {
            $this->from = 'ebohm@UP1-5CG34308F4.ad.univ-paris1.fr';
        }
    }

    public static function verifSiInvitation($fbParams) {
        if ($fbParams->actionFormulaireValider == 'envoiInvitation' && is_null($fbParams->titleEvent) == false && is_null($fbParams->descriptionEvent) == false) {
            return true;
        }
        return false;
    }

    private function phpMailInstance($userinfo): PHPMailer {
        $userinfo = (object) $userinfo;

        $formatter_day = IntlDateFormatter::create('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::FULL, date_default_timezone_get(), IntlDateFormatter::GREGORIAN, "EEEE dd/MM/yyyy HH'h'mm");
        $llllds = $formatter_day->format((new DateTime($this->modalCreneauStart))->getTimestamp());

        $mail = new PHPMailer(false);

        $icsData = FBUtils::icalCreationInvitation($this->organisateur, $this->listUserInfos, $this->modalCreneauStart, $this->modalCreneauEnd, $this->titleEvent, $this->descriptionEvent, $this->lieuEvent, $this->dtz);

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($this->organisateur->mail, $this->organisateur->displayName);
        $mail->addAddress($userinfo->mail, $userinfo->displayName);

        $mail->Subject = "Réunion {$this->titleEvent}, le $llllds";

        $body = "
Bonjour {$userinfo->displayName},

{$this->organisateur->displayName} vous invite à participer à l'événement suivant:

« {$this->titleEvent} », le $llllds

Description de l'événement :
« {$this->descriptionEvent} »

Lieu :
« {$this->lieuEvent} »

Cordialement,

{$this->organisateur->displayName}
";
        $mail->Body = $body;

        $mail->addStringAttachment($icsData, 'invitation.ics', 'base64', 'text/calendar; charset=UTF-8; method=REQUEST');

        return $mail;
    }

    private function _genereParametresMail($userinfo, $icsData = null) : stdClass {
        $userinfo = (object) $userinfo;

        if ($icsData === null)
            $icsData = FBUtils::icalCreationInvitationSabre($this->organisateur, $this->listUserInfos, $this->modalCreneauStart, $this->modalCreneauEnd, $this->titleEvent, $this->descriptionEvent, $this->lieuEvent, $this->dtz);

        $boundary = uniqid('boundary');

        $from = "From: {$this->from}";
        $to = "To: {$userinfo->mail}";

        $header = "$from".PHP_EOL."$to".PHP_EOL;
        $header .= "MIME-Version: 1.0".PHP_EOL;
        $header .= "Content-Type: multipart/alternative; boundary=\"$boundary\"".PHP_EOL;

        $message = "--$boundary".PHP_EOL;
        $message .= "Content-Type: text/plain; charset=utf-8".PHP_EOL;
        $message .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;
        $message .= "{$this->organisateur->displayName} vous a invité à {$this->titleEvent}.".PHP_EOL.PHP_EOL;
        $message .= "--$boundary".PHP_EOL;
        $message .= "Content-Type: text/calendar; charset=utf-8; method=REQUEST".PHP_EOL;
        $message .= "Content-Transfer-Encoding: base64".PHP_EOL.PHP_EOL;
        $message .= chunk_split(base64_encode($icsData));
        $message .= "--$boundary--".PHP_EOL;

        // pas pris en compte, si c'est ça qui empêche l'apparition de la bare dans thunderbird ... ben mince alors.
        $httpHeader = 'Content-type: multipart/alternative; boundary="' . $boundary . '"';
        $httpHeader .= 'Content-Disposition: inline; filename=invitation.ics';

        $stdObj = new stdClass();
        $stdObj->icsData = $icsData;
        $stdObj->boundary = $boundary;
        $stdObj->header = $header;
        $stdObj->httpHeader = $httpHeader;
        $stdObj->message = $message;

        return $stdObj;
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
                $usersend = $this->stdEnv->env != 'prod' ? ['mail' => $this->organisateur->mail, 'displayName' => ((object) $userinfo)->displayName] : $userinfo;
              
                $mail = $this->phpMailInstance(userinfo : $usersend);

                $eICS = new EasyPeasyICSUP1('Invitation');
        
                $dataics =['start' => (new DateTime($this->modalCreneauStart))->getTimestamp(), 'end' => (new DateTime($this->modalCreneauEnd))->getTimestamp(), 'summary' => $this->titleEvent, 'description' => $this->descriptionEvent, 'organizer' => $this->organisateur->displayName, 'organizer_email' => $this->organisateur->mail, 'location' => $this->lieuEvent ];

                foreach ($this->listUserInfos as $uid2 => $userinfo2) {
                    $userinfo2 = (object) $userinfo2;
                    $dataics['guests'][] = ['name' => $userinfo2->displayName, 'email' => $userinfo2->mail];
                }

                $eICS->addEvent($dataics);
                
                $mail = new PHPMailer(exceptions:false);

                $mail->setFrom('ebohm@UP1-5CG34308F4.ad.univ-paris1.fr');
                $mail->addAddress(((object) $usersend)->mail);

                $mail->Subject ="{$this->organisateur->displayName} vous a invité à {$this->titleEvent}";

                $invite = $eICS->render(output: false);

                $mail->Ical = $invite; 

                $mail->Body = 'Invitation event';

                $mail->AltBody = 'TEST ALT ALT MSG';
                $mail->CharSet = 'UTF-8';

                $stdMailInfo = $this->_genereParametresMail(userinfo: $userinfo, icsData: null);
                $mailSent = mail(to: ((object) $usersend)->mail, subject: "Invitation à un événement", message: $stdMailInfo->message, additional_headers: $stdMailInfo->header);
//                $mail->isSendmail();
//                if ($mail->send() == false)
//                    throw new Exception("erreur envoi mail");
                if (!$mailSent)
                    throw new Exception("erreur envoi mail par fonction php mail");


                

                $this->mailEffectivementEnvoye = true;
                $this->mailEffectivementEnvoyeKey = $idxSessionDate;
                if (!isset($this->mailEffectivementEnvoyeUids))
                    $this->mailEffectivementEnvoyeUids = [];

                $this->mailEffectivementEnvoyeUids[] = $uid;
                $_SESSION['inviteEnregistrement'][$idxSessionDate]['mails'][$uid] = array($userinfo['mail'], 'sended' => true, $userinfo);
            }
        }
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
