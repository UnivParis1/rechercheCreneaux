<?php

namespace RechercheCreneauxLib;

use BrunoBetioli\EasyPeasyICS\EasyPeasyICS;


class EasyPeasyICSUP1 extends EasyPeasyICS
{

    public function render(bool $output = true)
    {
        /* Start Variable */
        $ics = null;

        /* Add header */
        $ics .= 'BEGIN:VCALENDAR';
        $ics .= PHP_EOL . 'PRODID:-//Mailer//NONSGML v1.0//EN';
        $ics .= PHP_EOL . 'VERSION:2.0';
//        $ics .= PHP_EOL . 'CALSCALE:GREGORIAN';
        $ics .= PHP_EOL . 'METHOD:REQUEST';
        $ics .= PHP_EOL . 'X-WR-CALNAME: DSIUN-PAS / Etienne Bohm';
//        $ics .= PHP_EOL . 'X-WR-CALNAME:'.$this->calendarName;

        /* Add events */
        foreach ($this->events as $event) {
            $ics .= PHP_EOL . 'BEGIN:VEVENT';
            $ics .= PHP_EOL . 'UID:' . md5(uniqid(mt_rand(), true)) . '@EasyPeasyICS.php';
            $ics .= PHP_EOL . 'DTSTAMP:' . gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
            $ics .= PHP_EOL . 'DTSTART:' . gmdate('Ymd', $event['start']) . 'T' . gmdate('His', $event['start']) . 'Z';
            $ics .= PHP_EOL . 'DTEND:' . gmdate('Ymd', $event['end']) . 'T' . gmdate('His', $event['end']) . 'Z';
            if (!empty($event['organizer']) && !empty($event['organizer_email'])) {
                $ics .= PHP_EOL . 'ORGANIZER;CN=' . $event['organizer'] . ':mailto:' . $event['organizer_email'];
            }
            

            $ics .= PHP_EOL . 'SUMMARY:' . str_replace("\n", "\\n", $event['summary']);
            if (!empty($event['description'])) {
                $ics .= PHP_EOL . 'DESCRIPTION:' . str_replace('\n', '\\n', $event['description']);
            }
            if (!empty($event['location'])) {
                $ics .= PHP_EOL . 'LOCATION:' . str_replace('\n', '\\n', $event['location']);
            }

            // ajout UP1
            $ics .= PHP_EOL . 'CLASS:PUBLIC';
            $ics .= PHP_EOL . 'STATUS:CONFIRMED';
            $ics .= PHP_EOL . 'TRANSP:OPAQUE';
            // fin ajout

            if (key_exists('guests', $event) && is_array($event['guests']) && !empty($event['guests'])) {
                foreach ($event['guests'] as $guest) {
                    if (key_exists('name', $guest) && key_exists('email', $guest)) {
//                        $ics .= PHP_EOL . 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="' . $guest['name'] . '";X-NUM-GUESTS=0:mailto:' . $guest['email'];
                        $ics .= PHP_EOL . 'ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="' . $guest['name'] . ':mailto:' . $guest['email'];
                    }
                }
            }
//            $ics .= PHP_EOL . 'CREATED:' . gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
//            $ics .= PHP_EOL . 'LAST-MODIFIED:' . gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
//            $ics .= PHP_EOL . 'URL;VALUE=URI:' . $event['url'];
            $ics .= PHP_EOL . 'END:VEVENT';
        }

        /* Footer */
        $ics .= PHP_EOL . 'END:VCALENDAR';

        if ($output) {
            /* Output */
            header('Content-type: text/calendar; charset=utf-8');
            header('Content-Disposition: inline; filename='.$this->calendarName.'.ics');
            echo $ics;
        } else {
            return $ics;
        }
    }    

}