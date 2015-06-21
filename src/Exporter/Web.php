<?php

namespace LolEspCal\Exporter;

use LolEspCal\Calendar;

/**
 * Description of Web
 *
 * @author Jeremy
 */
class Web extends AbstractExporter
{
    /**
     * @var string
     */
    private $baseUrl = 'http://olol.github.io/lolesp-cal/';

    /**
     *
     * @var array
     */
    private $calendars = [
        'team'          => [],
        'region'        => [],
        'tournament'    => [],
    ];

    /**
     * @param \LolEspCal\Exporter\Map $map
     */
    public function __construct(Map $map)
    {
        parent::__construct($map);

        $that = $this;

        $this->map->getEmitter()->addListener(Map::EVENT_POST_EXPORT, function($event) use($that) {
            $that->finalizeExport();
        });
    }

    /**
     * @param \LolEspCal\Calendar $calendar
     */
    public function export(Calendar $calendar)
    {
        if ($calendar->getName()) {
            $this->calendars[$calendar->getType()][] = [
                'url'   => $this->getUrl($calendar->getFilename()),
                'name'  => str_replace('-', ' ', $calendar->getName()),
            ];
        }
    }

    /**
     *
     */
    private function finalizeExport()
    {
        // alphabetically sort
        foreach ($this->calendars as &$vals) {
            usort($vals, function($c1, $c2) {
                return strcasecmp($c1['name'], $c2['name']);
            });
        }

        file_put_contents('web/js/calendars.json', json_encode($this->calendars));
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getUrl($filename)
    {
        return $this->baseUrl . $filename;
    }
}
