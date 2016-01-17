<?php

namespace LolEspCal;

/**
 * Description of Schedule
 *
 * @author Jeremy
 */
class Schedule
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DateTime
     */
    private $scheduledTime;

    /**
     * @var array
     */
    private $tags;

    /**
     * @var array
     */
    private $match;

    /**
     * @var array
     */
    private $bracket;

    /**
     * @var array
     */
    private $tournament;

    /**
     * @var array
     */
    private $league;

    /**
     * @var array
     */
    private $rosters;

    public function __construct($id, \DateTime $scheduledTime, $tags, $match, $bracket, $tournament, $league, $rosters)
    {
        $this->id               = $id;
        $this->scheduledTime    = $scheduledTime;
        $this->tags             = $tags;
        $this->match            = $match;
        $this->bracket          = $bracket;
        $this->tournament       = $tournament;
        $this->league           = $league;
        $this->rosters          = $rosters;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getScheduledTime()
    {
        return $this->scheduledTime;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function getMatch()
    {
        return $this->match;
    }

    /**
     * @return array
     */
    public function getBracket()
    {
        return $this->bracket;
    }

    /**
     * @return array
     */
    public function getTournament()
    {
        return $this->tournament;
    }

    /**
     * @return array
     */
    public function getLeague()
    {
        return $this->league;
    }

    /**
     * @return array
     */
    public function getRosters()
    {
        return $this->rosters;
    }
}