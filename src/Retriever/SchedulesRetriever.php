<?php

namespace LolEspCal\Retriever;

use League\Event\EmitterInterface;
use League\Event\Event;

/**
 * Description of SchedulesRetriever
 *
 * @author Jeremy
 */
class SchedulesRetriever extends AbstractRetriever
{
    const EVENT_PRE_MATCH   = 'pre.schedules.retrieve';
    const EVENT_POST_MATCH  = 'post.schedules.retrieve';

    /**
     *
     */
    public function __construct()
    {
        $this->setUrl('http://api.lolesports.com/api/v1/scheduleItems?leagueId={leagueId}');
    }

    /**
     * @param int   $league
     *
     * @return null|array
     */
    public function retrieve($league = null)
    {
        $url = $this->getSchedulesUrlByLeagueId($league['id']);

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_PRE_MATCH), ['league' => &$league, 'url' => &$url]);
        }

        $schedules = json_decode(file_get_contents($url), true);
        unset($schedules['players']);

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_POST_MATCH), ['league' => &$league, 'schedules' => &$schedules]);
        }

        return $schedules;
    }

    public function getSchedulesUrlByLeagueId($leagueId)
    {
        return str_replace('{leagueId}', $leagueId, $this->getUrl());
    }
}
