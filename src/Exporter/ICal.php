<?php

namespace LolEspCal\Exporter;

use LolEspCal\Calendar;

/**
 * Description of ICal
 *
 * @author Jeremy
 */
class ICal extends AbstractExporter
{
    /**
     * @param \LolEspCal\Calendar $calendar
     */
    public function export(Calendar $calendar)
    {
        file_put_contents($calendar->getFilename(), (string) $calendar->getICalendar());
    }
}
