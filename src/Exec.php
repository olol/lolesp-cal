<?php

namespace LolEspCal;

use LolEspCal\Cacher\BlocksCacher;
use LolEspCal\Cacher\MatchCacher;
use LolEspCal\Calendar\Map as CalendarMap;
use LolEspCal\Calendar\Generator as CalendarGenerator;
use LolEspCal\Event\Generator as EventGenerator;
use LolEspCal\Exporter\Map as ExporterMap;
use LolEspCal\Retriever\BlocksRetriever;
use LolEspCal\Retriever\MatchRetriever;

/**
 * Description of Exec
 *
 * @author Jeremy
 */
class Exec
{
    /**
     * @var \LolEspCal\Calendar\Map
     */
    private $calendars;

    /**
     * @var \LolEspCal\Exporter\Map
     */
    private $exporters;

    /**
     * @var \LolEspCal\Retriever\BlocksRetriever
     */
    private $blocksRetriever;

    /**
     * @var \LolEspCal\Retriever\MatchRetriever
     */
    private $matchRetriever;

    /**
     * @var \LolEspCal\Cacher\BlocksCacher
     */
    private $blocksCacher;

    /**
     * @var \LolEspCal\Cacher\MatchCacher
     */
    private $matchCacher;

    /**
     * @var string
     */
    private $blocksCacheId;

    /**
     * @var array
     */
    private $options = [
        'generate.all'          => true,
        'generate.team'         => true, // ['c9', 'fnc', 'skt']
//        'generate.region'       => true, // @todo
        'generate.tournament'   => true, // ['na-season-3-spring-split']
//        'generators'            => ['all' => true, 'team' => true, 'region' => true, 'tournament' => true], // @todo
        'startDate'             => 'this week', // @todo
        'endDate'               => '+6 days', // @todo
        'cache'                 => true,
        'exporters'             => ['ICal', 'Web'],
    ];

    /**
     * @param array                                 $options
     * @param \LolEspCal\Retriever\BlocksRetriever  $blocksRetriever
     * @param \LolEspCal\Retriever\MatchRetriever   $matchRetriever
     */
    public function __construct($options = [], BlocksRetriever $blocksRetriever = null, MatchRetriever $matchRetriever = null)
    {
        $this->setBlocksRetriever($blocksRetriever);
        $this->setMatchRetriever($matchRetriever);
        $this->setOptions($options);

        $this->listenToEvents();
    }

    /**
     * Retrieve matches then generates calendars internally.
     */
    public function generate()
    {
        $blocks = $this->getBlocksRetriever()->retrieve();

//        $cpt = 0;
        foreach ($blocks as $block) {
            foreach ($block['matches'] as $matchId) {
//                if ($cpt == 100) {
//                    echo 'Stopping at ' . $cpt . ' matches (pheewww).' . "\n";
//                    break 2;
//                }

                $this->getMatchRetriever()->retrieve($matchId);

//                $cpt++;
            }
        }
    }

    /**
     * Exports generated calendars.
     */
    public function export()
    {
        $this->getExporters()->exportAll($this->getCalendars());
    }

    /**
     * Render this specific calendar
     *
     * @param \LolEspCal\Calendar|string    $calendar
     */
    public function render($calendar)
    {
        if (is_string($calendar)) {
            $calendar = $this->getCalendars()->get($calendar)->get();
        }

        $calendar->getICalendar()->render();
    }

    /**
     * This will listen to events when blocks or matches are retrieved in order
     * to create / fill up calendars or handle cache if activated.
     */
    private function listenToEvents()
    {
        $that = $this;


        if (true === $this->getOption('cache')) {
            $this->getBlocksRetriever()->getEmitter()->addListener(BlocksRetriever::EVENT_PRE_BLOCKS, function($event, $params) use($that) {
                $that->checkForGettingBlocksCache($params['url']);
            });
        }

        $this->getBlocksRetriever()->getEmitter()->addListener(BlocksRetriever::EVENT_POST_BLOCKS, function($event, $params) use($that) {
            $that->checkForSettingBlocksCache($params['blocks']);
        });


        $this->getMatchRetriever()->getEmitter()->addListener(MatchRetriever::EVENT_PRE_MATCH, function($event, $params) use($that) {
            $that->checkForGettingMatchCache($params['matchId'], $params['url']);
        });

        $this->getMatchRetriever()->getEmitter()->addListener(MatchRetriever::EVENT_POST_MATCH, function($event, $params) use($that) {
            $that->checkForNewCalendar($params['match']);
        });

        $this->getMatchRetriever()->getEmitter()->addListener(MatchRetriever::EVENT_POST_MATCH, function($event, $params) use($that) {
            $that->checkForAddingEvent($params['match']);
        });

        if (true === $this->getOption('cache')) {
            $this->getMatchRetriever()->getEmitter()->addListener(MatchRetriever::EVENT_POST_MATCH, function($event, $params) use($that) {
                $that->checkForSettingMatchCache($params['matchId'], $params['match']);
            });
        }
    }

    /**
     * @param array $match
     */
    private function checkForNewCalendar(&$match)
    {
        $calendarGenerator = new CalendarGenerator();

        // Do we want to generate the calendar containing all matches ?
        if (true === $this->getOption('generate.all')) {
            // Does this calendar already exists ?
            if (false === $this->getCalendars()->containsKey('all')) {
                $calendar = $calendarGenerator->create('all');
                $this->getCalendars()->set('all', $calendar);
            }
        }

        // Do we want to generate a team specific calendar for each team ?
        if (false !== $this->getOption('generate.team')) {
            $teams = [
                strtolower(isset($match['contestants']['blue']) ? $match['contestants']['blue']['acronym'] : ''),
                strtolower(isset($match['contestants']['red']) ? $match['contestants']['red']['acronym'] : ''),
            ];

            foreach ($teams as $team) {
                // Do we want to generate the team specific calendar for this team and does it already exists ?
                if ((true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($team, $this->getOption('generate.team')))) &&
                     false === $this->getCalendars()->containsKey('team-' . $team)) {
                    $calendar = $calendarGenerator->create('team', $team);
                    $this->getCalendars()->set('team-' . $team, $calendar);
                }
            }
        }

        // Do we want to generate a tournament specific calendar for each tournament ?
        if (false !== $this->getOption('generate.tournament')) {
            $tournament = str_replace(' ', '-', strtolower($match['tournament']['name']));
            // Do we want to generate the tournament specific calendar for this tournament and does it already exists ?
            if ((true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && in_array($tournament, $this->getOption('generate.tournament')))) &&
                 false === $this->getCalendars()->containsKey('tournament-' . $tournament)) {
                $calendar = $calendarGenerator->create('tournament', $tournament);
                $this->getCalendars()->set('tournament-' . $tournament, $calendar);
            }
        }
    }

    /**
     * @param array $match
     */
    private function checkForAddingEvent(&$match)
    {
        $teams = [
            strtolower(isset($match['contestants']['blue']) ? $match['contestants']['blue']['acronym'] : ''),
            strtolower(isset($match['contestants']['red']) ? $match['contestants']['red']['acronym'] : ''),
        ];
        $tournament = str_replace(' ', '-', strtolower($match['tournament']['name']));

        if (true === $this->getOption('generate.all') ||
            true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && (in_array($teams[0], $this->getOption('generate.team')) || in_array($teams[1], $this->getOption('generate.team')))) ||
            true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && (in_array($tournament, $this->getOption('generate.tournament'))))) {

            $event = (new EventGenerator())->createFromMatch($match);
            if (is_null($event)) {
                return;
            }

            $addToCalendars = [];
            if (true === $this->getOption('generate.all')) {
                $addToCalendars[] = 'all';
            }
            if (true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($teams[0], $this->getOption('generate.team')))) {
                $addToCalendars[] = 'team-' . $teams[0];
            }
            if (true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($teams[1], $this->getOption('generate.team')))) {
                $addToCalendars[] = 'team-' . $teams[1];
            }
            if (true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && in_array($tournament, $this->getOption('generate.tournament')))) {
                $addToCalendars[] = 'tournament-' . $tournament;
            }

            foreach ($addToCalendars as $calendarName) {
                $calendar = $this->getCalendars()->get($calendarName)->get();
                $calendar->getICalendar()->addEvent($event);
            }
        }
    }

    /**
     * If the cache is available for this match, we change the url to redirect
     * to the cached file instead.
     *
     * @param int       $matchId
     * @param string    $url
     */
    private function checkForGettingMatchCache($matchId, &$url)
    {
        if (true === $this->getMatchCacher()->has($matchId)) {
            $url = $this->getMatchCacher()->getCacheFilename($matchId);
        }
    }

    /**
     * If the cache is not available for this match, we create it.
     *
     * @param int       $matchId
     * @param array     $match
     */
    private function checkForSettingMatchCache($matchId, &$match)
    {
        if (false === $this->getMatchCacher()->has($matchId)) {
            $this->getMatchCacher()->set($matchId, json_encode($match));
        }
    }

    /**
     * If the cache is available for the blocks, we change the url to redirect
     * to the cached file instead.
     *
     * @param string    $url
     */
    private function checkForGettingBlocksCache(&$url)
    {
        if (true === $this->getBlocksCacher()->has($this->getBlocksCacheId())) {
            $url = $this->getBlocksCacher()->getCacheFilename($this->getBlocksCacheId());
        }
    }

    /**
     * If the cache is not available for the blocks, we create it.
     *
     * @param array     $blocks
     */
    private function checkForSettingBlocksCache(&$blocks)
    {
        if (false === $this->getBlocksCacher()->has($this->getBlocksCacheId())) {
            $this->getBlocksCacher()->set($this->getBlocksCacheId(), json_encode($blocks));
        }
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options = [])
    {
        $this->options = array_merge($this->options, $options);

        foreach ($this->options as $name => $option) {
            $this->setOption($name, $option);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setOption($name, $value)
    {
        switch($name) {
            case 'startDate':
                if (is_string($value)) {
                    $value = (new \DateTime())->modify($value);
                }
            case 'endDate':
                if (is_string($value)) {
                    $value = (new \DateTime($this->getOption('startDate')->format('Y-m-d H:i:s')))->modify($value);
                }
                break;
            case 'exporters':
                // Initializing exporters by creating the class and adding it to the exporters map
                foreach ($value as $k => $v) {
                    if (is_string($v)) {
                        $exporterClassName = '\LolEspCal\Exporter\\' . $v;
                        if (class_exists($exporterClassName)) {
                            $value[$k] = new $exporterClassName($this->getExporters());
                            $this->getExporters()->set($v, $value[$k]);
                        } else {
                            // throw ...
                        }
                    }
                }
                break;
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name] : null;
    }

    /**
     * @param \LolEspCal\Calendar\Map|null     $calendars
     *
     * @return $this
     */
    public function setCalendars(CalendarMap $calendars = null)
    {
        $this->calendars = $calendars;

        return $this;
    }

    /**
     * Get the calendars map object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Calendar\Map
     */
    public function getCalendars()
    {
        if (is_null($this->calendars)) {
            $this->setCalendars(new CalendarMap());
        }

        return $this->calendars;
    }

    /**
     * @param \LolEspCal\Exporter\Map|null     $exporters
     *
     * @return $this
     */
    public function setExporters(ExporterMap $exporters = null)
    {
        $this->exporters = $exporters;

        return $this;
    }

    /**
     * Get the exporters map object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Exporter\Map
     */
    public function getExporters()
    {
        if (is_null($this->exporters)) {
            $this->setExporters(new ExporterMap());
        }

        return $this->exporters;
    }

    /**
     * @param \LolEspCal\Retriever\BlocksRetriever|null     $blocksRetriever
     *
     * @return $this
     */
    public function setBlocksRetriever(BlocksRetriever $blocksRetriever = null)
    {
        $this->blocksRetriever = $blocksRetriever;

        return $this;
    }

    /**
     * Get the blocksRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\BlocksRetriever
     */
    public function getBlocksRetriever()
    {
        if (is_null($this->blocksRetriever)) {
            $this->setBlocksRetriever(new BlocksRetriever());
        }

        return $this->blocksRetriever;
    }

    /**
     * @param \LolEspCal\Retriever\MatchRetriever|null  $matchRetriever
     *
     * @return $this
     */
    public function setMatchRetriever(MatchRetriever $matchRetriever = null)
    {
        $this->matchRetriever = $matchRetriever;

        return $this;
    }

    /**
     * Get the matchRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\MatchRetriever
     */
    public function getMatchRetriever()
    {
        if (is_null($this->matchRetriever)) {
            $this->setMatchRetriever(new MatchRetriever());
        }

        return $this->matchRetriever;
    }

    /**
     * @param \LolEspCal\Cacher\BlocksCacher|null    $blocksCacher
     *
     * @return $this
     */
    public function setBlocksCacher(BlocksCacher $blocksCacher = null)
    {
        $this->blocksCacher = $blocksCacher;

        return $this;
    }

    /**
     * Get the blocksCacher object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Cacher\BlocksCacher
     */
    public function getBlocksCacher()
    {
        if (is_null($this->blocksCacher)) {
            $this->setBlocksCacher(new BlocksCacher());
        }

        return $this->blocksCacher;
    }

    /**
     * @param string|null   $blocksCacheId
     *
     * @return $this
     */
    public function setBlocksCacheId($blocksCacheId = null)
    {
        $this->blocksCacheId = $blocksCacheId;

        return $this;
    }

    /**
     * Get the blocksCacheId object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return string
     */
    public function getBlocksCacheId()
    {
        if (is_null($this->blocksCacheId)) {
            $this->setBlocksCacheId(date('Y-m-d'));
        }

        return $this->blocksCacheId;
    }

    /**
     * @param \LolEspCal\Cacher\MatchCacher|null    $matchCacher
     *
     * @return $this
     */
    public function setMatchCacher(MatchCacher $matchCacher = null)
    {
        $this->matchCacher = $matchCacher;

        return $this;
    }

    /**
     * Get the matchCacher object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Cacher\MatchCacher
     */
    public function getMatchCacher()
    {
        if (is_null($this->matchCacher)) {
            $this->setMatchCacher(new MatchCacher());
        }

        return $this->matchCacher;
    }
}
