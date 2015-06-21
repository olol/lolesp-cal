<?php

namespace LolEspCal\Retriever;

use League\Event\EmitterInterface;
use League\Event\Event;

/**
 * Description of BlocksRetriever
 *
 * @author Jeremy
 */
class BlocksRetriever extends AbstractRetriever
{
    const EVENT_PRE_BLOCKS   = 'pre.blocks.retrieve';
    const EVENT_POST_BLOCKS  = 'post.blocks.retrieve';

    /**
     *
     */
    public function __construct()
    {
        $this->setUrl('http://euw.lolesports.com/api/programming.json?parameters[method]=all');
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
            $this->getEmitter()->emit(Event::named(self::EVENT_PRE_BLOCKS), ['url' => &$url]);
        }

        $blocks = json_decode(file_get_contents($url), true);

        if ($this->getEmitter() instanceof EmitterInterface) {
            $this->getEmitter()->emit(Event::named(self::EVENT_POST_BLOCKS), ['blocks' => &$blocks]);
        }

        return $blocks;
    }
}
