<?php

namespace LolEspCal\Retriever;

use League\Event\EmitterInterface;
use League\Event\Event;

/**
 * Description of LeaguesRetriever
 *
 * @author Jeremy
 */
class LeaguesRetriever extends AbstractRetriever
{
    const EVENT_PRE_LEAGUES     = 'pre.leagues.retrieve';
    const EVENT_POST_LEAGUES    = 'post.leagues.retrieve';

    /**
     *
     */
    public function __construct()
    {
        $this->setUrl('http://api.lolesports.com/api/v1/leagues');
    }

    /**
     * @param mixed $param
     *
     * @return null|array
     */
    public function retrieve($param = null)
    {
        $url = $this->getUrl();

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_PRE_LEAGUES), ['url' => &$url]);
        }

        $leagues = json_decode(file_get_contents($url), true);
        if (isset($leagues['leagues'])) {
            $leagues = &$leagues['leagues'];
        }

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_POST_LEAGUES), ['blocks' => &$leagues]);
        }

        return $leagues;
    }
}
