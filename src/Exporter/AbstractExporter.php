<?php

namespace LolEspCal\Exporter;

use LolEspCal\Calendar;

/**
 * Description of AbstractExporter
 *
 * @author Jeremy
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var \LolEspCal\Exporter\Map
     */
    protected $map;

    /**
     * @param \LolEspCal\Exporter\Map $map
     */
    public function __construct(Map $map)
    {
        $this->map = $map;
    }

    /**
     * @param \LolEspCal\Calendar $calendar
     */
    abstract public function export(Calendar $calendar);
}
