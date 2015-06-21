<?php

namespace LolEspCal\Cacher;

/**
 * Description of MatchCacher
 *
 * @author Jeremy
 */
class MatchCacher extends AbstractCacher
{
    /**
     * @param int  $matchId
     *
     * @return bool
     */
    public function has($matchId)
    {
        return file_exists($this->getCacheFilename($matchId));
    }

    /**
     * @param int $matchId
     *
     * @return string
     */
    public function get($matchId)
    {
        return file_get_contents($this->getCacheFilename($matchId));
    }

    /**
     * @param int $matchId
     * @param string $match
     */
    public function set($matchId, $match)
    {
        file_put_contents($this->getCacheFilename($matchId), $match);
    }

    /**
     * @param int $matchId
     *
     * @return string
     */
    public function getCacheFilename($matchId)
    {
        return $this->getCacheDir() . 'match-' . $matchId . '.json';
    }
}
