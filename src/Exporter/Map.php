<?php

namespace LolEspCal\Exporter;

use PhpCollection\Map as PhpCollectionMap;

use League\Event\EmitterAwareInterface;
use League\Event\EmitterAwareTrait;
use League\Event\EmitterInterface;
use League\Event\Event;

/**
 * Description of Map
 *
 * @author Jeremy
 */
class Map extends PhpCollectionMap implements EmitterAwareInterface
{
    use EmitterAwareTrait;

    const EVENT_PRE_EXPORT   = 'pre.export';
    const EVENT_POST_EXPORT  = 'post.export';

    public function exportAll($calendars)
    {
        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_PRE_EXPORT));
        }

        foreach ($this as $exporter) {
            if ($exporter instanceof ExporterInterface) {
                foreach ($calendars as $calendar) {
                    $exporter->export($calendar);
                }
            }
        }

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_POST_EXPORT));
        }
    }
}
