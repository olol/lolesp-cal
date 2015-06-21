<?php

namespace LolEspCal\Exporter;

use LolEspCal\Calendar;

/**
 * Description of ExporterInterface
 *
 * @author Jeremy
 */
interface ExporterInterface
{
    /**
     * @param \LolEspCal\Calendar $calendar
     */
    public function export(Calendar $calendar);
}
