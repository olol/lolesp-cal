<?php

namespace LolEspCal\Cacher;

/**
 * Description of BlocksCacher
 *
 * @author Jeremy
 */
class BlocksCacher extends AbstractCacher
{
    /**
     * @param string  $id
     *
     * @return bool
     */
    public function has($id)
    {
        return file_exists($this->getCacheFilename($id));
    }

    /**
     * @param string $id
     *
     * @return string
     */
    public function get($id)
    {
        return file_get_contents($this->getCacheFilename($id));
    }

    /**
     * @param string $id
     * @param string $blocks
     */
    public function set($id, $blocks)
    {
        file_put_contents($this->getCacheFilename($id), $blocks);
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function getCacheFilename($id)
    {
        return $this->getCacheDir() . 'blocks-' . $id . '.json';
    }
}
