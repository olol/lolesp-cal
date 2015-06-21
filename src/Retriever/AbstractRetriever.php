<?php

namespace LolEspCal\Retriever;

use League\Event\EmitterAwareInterface;
use League\Event\EmitterAwareTrait;

/**
 * Description of AbstractRetriever
 *
 * @author Jeremy
 */
abstract class AbstractRetriever implements EmitterAwareInterface
{
    use EmitterAwareTrait;

    /**
     * @var string
     */
    private $url;

    /**
     * @param mixed $param
     */
    abstract function retrieve($param = null);

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
