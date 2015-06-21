<?php

namespace LolEspCal\Cacher;

/**
 * Description of AbstractCacher
 *
 * @author Jeremy
 */
abstract class AbstractCacher
{
    /**
     * @var string
     */
    private $cacheDir = 'cache/';

    /**
     * @param mixed $id
     *
     * @return bool
     */
    abstract public function has($id);

    /**
     * @param mixed $id
     *
     * @return string
     */
    abstract public function get($id);

    /**
     * @param mixed     $id
     * @param string    $content
     */
    abstract public function set($id, $content);

    /**
     * @return string
     */
    abstract public function getCacheFilename($param);

    /**
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }
}
