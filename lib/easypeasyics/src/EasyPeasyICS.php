<?php
namespace BrunoBetioli\EasyPeasyICS;

/* ------------------------------------------------------------------------ */
/* EasyPeasyICS
/* ------------------------------------------------------------------------ */
/* Bruno Betioli, brunobetioli@yahoo.com.br
/* Github: https://gihub.com/brunobetioli
/*
/* Manuel Reinhard, manu@sprain.ch
/* Twitter: @sprain
/* Web: www.sprain.ch
/*
/* Built with inspiration by
/" http://stackoverflow.com/questions/1463480/how-can-i-use-php-to-dynamically-publish-an-ical-file-to-be-read-by-google-calend/1464355#1464355
/* ------------------------------------------------------------------------ */
/* History:
/* 2010/12/17 - Manuel Reinhard - when it all started
/* 2021/09/15 - Bruno Betioli - altered some things from original project
/* ------------------------------------------------------------------------ */

class EasyPeasyICS
{
    protected $calendarName;
    protected $events = [];

    /**
     * Constructor
     * @param string $calendarName
     */
    public function __construct(string $calendarName = null)
    {
        $this->calendarName = $calendarName;
    }

    /**
     * Add event to calendar
     * @param array $mailEvent
     */
    public function addEvent(array $mailEvent)
    {
        $this->events[] = array_merge(array_fill_keys(['start', 'end', 'summary', 'description', 'url', 'organizer', 'organizer_email', 'location'], null), $mailEvent);
    }

    public function render(bool $output = true)
    {
        /* Start Variable */
        $ics = null;

        /* Add header */
        $ics .= 'BEGIN:VCALENDAR';
        $ics .= PHP_EOL . 'PRODID:-//Mailer//NONSGML v1.0//EN';
        $ics .= PHP_EOL . 'VERSION:2.0';
        $ics .= PHP_EOL . 'CALSCALE:GREGORIAN';
        $ics .= PHP_EOL . 'METHOD:PUBLISH';
        $ics .= PHP_EOL . 'X-WR-CALNAME:'.$this->calendarName;

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
            if (key_exists('guests', $event) && is_array($event['guests']) && !empty($event['guests'])) {
                foreach ($event['guests'] as $guest) {
                    if (key_exists('name', $guest) && key_exists('email', $guest)) {
                        $ics .= PHP_EOL . 'ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN="' . $guest['name'] . '";X-NUM-GUESTS=0:mailto:' . $guest['email'];
                    }
                }
            }
            $ics .= PHP_EOL . 'CREATED:' . gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
            $ics .= PHP_EOL . 'LAST-MODIFIED:' . gmdate('Ymd') . 'T' . gmdate('His') . 'Z';
            $ics .= PHP_EOL . 'URL;VALUE=URI:' . $event['url'];
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
