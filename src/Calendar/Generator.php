<?php

namespace LolEspCal\Calendar;

use Eluceo\iCal\Component\Calendar as iCalendar;
use LolEspCal\Calendar;

/**
 * Description of Generator
 *
 * @author Jeremy
 */
class Generator
{
    /**
     * @var string
     */
    private $calendarDir = 'calendars/';

    /**
     * @param string    $type
     * @param string    $name
     *
     * @return \LolEspCal\Calendar
     */
    function create($type = 'all', $name = null)
    {
        $iCalendar = new iCalendar('www.lolesports.com|' . $this->calendarName($this->getFullName($type, $name)));
        $iCalendar->setName($this->getReadableName($type, $name));

        $calendar = new Calendar();
        $calendar
            ->setICalendar($iCalendar)
            ->setType($type)
            ->setName($name)
            ->setFilename(str_replace(' ', '-', $this->calendarFilename($this->getFullName($type, $name))))
        ;

        return $calendar;
    }

    /**
     * @param array     $match
     * @param string    $type
     *
     * @return string
     */
    private function calendarName($type)
    {
        return 'cal-' . $type;
    }

    private function calendarFilename($type)
    {
        return $this->getCalendarDir() . $this->calendarName($type) . '.ics';
    }

    /**
     * @param string    $calendarDir
     */
    public function setCalendarDir($calendarDir)
    {
        $this->calendarDir = $calendarDir;
    }

    /**
     * @return string
     */
    public function getCalendarDir()
    {
        return $this->calendarDir;
    }

    /**
     * @param string $type
     * @param string $name
     *
     * @return string
     */
    public function getFullName($type, $name = null)
    {
        $fullName = $type;
        if (!is_null($name)) {
            $fullName .= '-' . $name;
        }

        return $fullName;
    }

    /**
     * @param string    $type
     * @param string    $name
     *
     * @return string
     */
    public function getReadableName($type, $name = null)
    {
        switch($type) {
            case 'team':
            case 'region':
                $name = strtoupper($name);
            case 'tournament':
                $name = str_replace('-', ' ', ucfirst($name));
                break;
            default:
                $name = 'matches';
                break;
        }

        return 'LoL eSports ' . $name;
    }
}
