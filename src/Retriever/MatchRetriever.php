<?php

namespace LolEspCal\Retriever;

use League\Event\EmitterInterface;
use League\Event\Event;

/**
 * Description of MatchRetriever
 *
 * @author Jeremy
 */
class MatchRetriever extends AbstractRetriever
{
    const EVENT_PRE_MATCH   = 'pre.match.retrieve';
    const EVENT_POST_MATCH  = 'post.match.retrieve';

    /**
     *
     */
    public function __construct()
    {
        $this->setUrl('http://euw.lolesports.com/api/match/{matchId}.json');
    }

    /**
     * @param int   $matchId
     *
     * @return null|array
     */
    public function retrieve($matchId = null)
    {
        $url = $this->getMatchUrlByMatchId($matchId);

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_PRE_MATCH), ['matchId' => $matchId, 'url' => &$url]);
        }

        $match = json_decode(file_get_contents($url), true);

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_POST_MATCH), ['matchId' => $matchId, 'match' => &$match]);
        }

        return $match;
    }

    public function getMatchUrlByMatchId($matchId)
    {
        return str_replace('{matchId}', $matchId, $this->getUrl());
    }
}
