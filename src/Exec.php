<?php

namespace LolEspCal;

use LolEspCal\Calendar\Map as CalendarMap;
use LolEspCal\Calendar\Generator as CalendarGenerator;

use LolEspCal\Event\Generator as EventGenerator;

use LolEspCal\Exporter\Map as ExporterMap;

use LolEspCal\Retriever\LeaguesRetriever;
use LolEspCal\Retriever\SchedulesRetriever;
use LolEspCal\Retriever\PlayersRetriever;
use LolEspCal\Retriever\TeamsRetriever;

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
     * @var \LolEspCal\Retriever\LeaguesRetriever
     */
    private $leaguesRetriever;

    /**
     * @var \LolEspCal\Retriever\SchedulesRetriever
     */
    private $schedulesRetriever;

    /**
     * @var array
     */
    private $options = [
        'generate.all'          => true,
        'generate.team'         => true, // ['c9', 'fnc', 'skt']
//        'generate.player'       => true, // @todo ['piglet', 'bjergsen', 'amazing']
//        'generate.region'       => true, // @todo
        'generate.tournament'   => true, // ['na-season-3-spring-split']
//        'generators'            => ['all' => true, 'team' => true, 'region' => true, 'tournament' => true], // @todo
        'startDate'             => 'this week', // @todo
        'endDate'               => '+6 days', // @todo
        'cache'                 => false, // @todo
        'exporters'             => ['ICal', 'Web'],
    ];

    /**
     * @param array                                     $options
     * @param \LolEspCal\Retriever\LeaguesRetriever     $leaguesRetriever
     * @param \LolEspCal\Retriever\SchedulesRetriever   $schedulesRetriever
     */
    public function __construct($options = [], LeaguesRetriever $leaguesRetriever = null, SchedulesRetriever $schedulesRetriever = null)
    {
        $this->setLeaguesRetriever($leaguesRetriever);
        $this->setSchedulesRetriever($schedulesRetriever);
        $this->setOptions($options);

        $this->listenToEvents();
    }

    /**
     * Retrieve matches then generates calendars internally.
     */
    public function generate()
    {
//        $this->getPlayersRetriever()->retrieve();
//        $this->getTeamsRetriever()->retrieve();
        $leagues = $this->getLeaguesRetriever()->retrieve();

//        $cpt = 0;
        foreach ($leagues as &$league) {
//            if ($cpt == 100) {
//                echo 'Stopping at ' . $cpt . ' matches (pheewww).' . "\n";
//                break 2;
//            }

            $this->getSchedulesRetriever()->retrieve($league);

//            $cpt++;
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

        $this->getSchedulesRetriever()->getEmitter()->addListener(SchedulesRetriever::EVENT_POST_MATCH, function($event, $params) use($that) {
            $that->checkForNewCalendar($params['schedules'], $params['league']);
        });

        $this->getSchedulesRetriever()->getEmitter()->addListener(SchedulesRetriever::EVENT_POST_MATCH, function($event, $params) use($that) {
            $that->checkForAddingEvent($params['schedules'], $params['league']);
        });
    }

    /**
     * @param array $schedules
     */
    private function checkForNewCalendar(&$schedules, &$league)
    {
        $calendarGenerator = new CalendarGenerator();

        // Do we want to generate the calendar containing all matches ?
        if (true === $this->getOption('generate.all')) {
            // Does this calendar already exists ?
            if (false === $this->getCalendars()->containsKey('all')) {
                $calendar = $calendarGenerator->create('all', null, 'https://lolstatic-a.akamaihd.net/frontpage/apps/prod/lolesports_feapp/en_US/f2c8dc256c9e95c783215343288c8d234bcf83ad/assets/img/sprites/site-logo.png');
                $this->getCalendars()->set('all', $calendar);
            }
        }

        // Do we want to generate a team specific calendar for each team ?
        if (false !== $this->getOption('generate.team')) {
            foreach ($schedules['highlanderTournaments'] as $highlanderTournament) {
                foreach ($highlanderTournament['rosters'] as $roster) {
                    if (!isset($roster['team']) || strlen($roster['name']) > 3) {
                        continue;
                    }
                    $teamName = $roster['name'];
                    // Do we want to generate the team specific calendar for this team and does it already exists ?
                    if ((true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($teamName, $this->getOption('generate.team')))) &&
                         false === $this->getCalendars()->containsKey('team-' . $teamName)) {
                        $team = $this->findTeamFromSchedules($schedules, $roster['team']);
                        $calendar = $calendarGenerator->create('team', $teamName);
                        if ($team) {
                            $calendar->setImageUrl(isset($team['altLogoUrl']) ? $team['altLogoUrl'] : $team['logoUrl']);
                        }
                        $this->getCalendars()->set('team-' . $teamName, $calendar);
                    }
                }
            }
        }

        // Do we want to generate a tournament specific calendar for each tournament ?
        if (false !== $this->getOption('generate.tournament')) {
            foreach ($schedules['highlanderTournaments'] as $highlanderTournament) {
                $tournament = str_replace('_', '-', strtolower($highlanderTournament['title']));
                // Do we want to generate the tournament specific calendar for this tournament and does it already exists ?
                if ((true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && in_array($tournament, $this->getOption('generate.tournament')))) &&
                     false === $this->getCalendars()->containsKey('tournament-' . $tournament)) {
                    $calendar = $calendarGenerator->create('tournament', $tournament);
                    if (isset($league['logoUrl'])) {
                        $calendar->setImageUrl($league['logoUrl']);
                    }
                    $this->getCalendars()->set('tournament-' . $tournament, $calendar);
                }
            }
        }
    }

    /**
     * @param array     $schedules
     * @param string    $matchId
     * @param string    $bracketId
     * @param string    $tournamentId
     *
     * @return array|null
     */
    private function findMatchFromSchedules(&$schedules, $matchId, $bracketId, $tournamentId)
    {
        foreach ($schedules['highlanderTournaments'] as &$highlanderTournament) {
            if ($highlanderTournament['id'] == $tournamentId || is_null($tournamentId)) {
                if (isset($highlanderTournament['brackets'][$bracketId]['matches'][$matchId])) {
                   return $highlanderTournament['brackets'][$bracketId]['matches'][$matchId];
                }
            }
        }

        return null;
    }

    /**
     * @param array     $schedules
     * @param string    $matchId
     * @param string    $bracketId
     * @param string    $tournamentId
     *
     * @return array|null
     */
    private function findBracketFromSchedules(&$schedules, $bracketId, $tournamentId)
    {
        foreach ($schedules['highlanderTournaments'] as &$highlanderTournament) {
            if ($highlanderTournament['id'] == $tournamentId || is_null($tournamentId)) {
                if (isset($highlanderTournament['brackets'][$bracketId])) {
                   return $highlanderTournament['brackets'][$bracketId];
                }
            }
        }

        return null;
    }

    /**
     * @param array     $schedules
     * @param string    $rosterId
     * @param string    $tournamentId
     *
     * @return array|null
     */
    private function findRosterFromSchedules(&$schedules, $rosterId, $tournamentId = null)
    {
        foreach ($schedules['highlanderTournaments'] as &$highlanderTournament) {
            if ($highlanderTournament['id'] == $tournamentId || is_null($tournamentId)) {
                if (isset($highlanderTournament['rosters'][$rosterId])) {
                    return $highlanderTournament['rosters'][$rosterId];
                }
            }
        }

        return null;
    }

    /**
     * @param array $schedules
     * @param int   $tournamentId
     *
     * @return array|null
     */
    private function findTournamentFromSchedules(&$schedules, $tournamentId)
    {
        foreach ($schedules['highlanderTournaments'] as &$highlanderTournament) {
            if ($highlanderTournament['id'] == $tournamentId) {
                return $highlanderTournament;
            }
        }

        return null;
    }

    /**
     * @param array $schedules
     * @param int   $teamId
     *
     * @return array|null
     */
    private function findTeamFromSchedules(&$schedules, $teamId)
    {
        foreach ($schedules['teams'] as &$team) {
            if ($team['id'] == $teamId) {
                return $team;
            }
        }

        return null;
    }

    /**
     * @param array $match
     */
    private function checkForAddingEvent(&$schedules, &$league)
    {
        foreach ($schedules['scheduleItems'] as $scheduleItem) {
            if (!isset($scheduleItem['tournament']) || !isset($scheduleItem['bracket']) || !isset($scheduleItem['match'])) {
                continue;
            }

            $tournament = $this->findTournamentFromSchedules($schedules, $scheduleItem['tournament']);
            $tournamentName = str_replace('_', '-', $tournament['title']);
            $bracket = $this->findBracketFromSchedules($schedules, $scheduleItem['bracket'], $scheduleItem['tournament']);
            if (!isset($bracket['name'])) {
                continue;
            }
            $match = $this->findMatchFromSchedules($schedules, $scheduleItem['match'], $scheduleItem['bracket'], $scheduleItem['tournament']);
            $rosters = [];
            if (!isset($match['input'])) {
                continue;
            }
            foreach ($match['input'] as $input) {
                if (!isset($input['roster'])) {
                    continue 2;
                }
                $roster = $this->findRosterFromSchedules($schedules, $input['roster']);
                if (!isset($roster['team']) || strlen($roster['name']) > 3) {
                    continue 2;
                }
                $roster['team'] = $this->findTeamFromSchedules($schedules, $roster['team']);
                $rosters[] = $roster;
            }
            $schedule = new Schedule(
                $scheduleItem['id'],
                new \DateTime($scheduleItem['scheduledTime']),
                $scheduleItem['tags'],
                $match,
                $bracket,
                $tournament,
                $league,
                $rosters
            );

            if (true === $this->getOption('generate.all') ||
                true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && (in_array($rosters[0]['name'], $this->getOption('generate.team')) || in_array($rosters[1]['name'], $this->getOption('generate.team')))) ||
                true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && (in_array($tournamentName, $this->getOption('generate.tournament'))))) {

                $event = (new EventGenerator())->createFromSchedule($schedule);
                if (is_null($event)) {
                    return;
                }

                $addToCalendars = [];
                if (true === $this->getOption('generate.all')) {
                    $addToCalendars[] = 'all';
                }
                if (true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($rosters[0]['name'], $this->getOption('generate.team')))) {
                    $addToCalendars[] = 'team-' . $rosters[0]['name'];
                }
                if (true === $this->getOption('generate.team') || (is_array($this->getOption('generate.team')) && in_array($rosters[1]['name'], $this->getOption('generate.team')))) {
                    $addToCalendars[] = 'team-' . $rosters[1]['name'];
                }
                if (true === $this->getOption('generate.tournament') || (is_array($this->getOption('generate.tournament')) && in_array($tournamentName, $this->getOption('generate.tournament')))) {
                    $addToCalendars[] = 'tournament-' . $tournamentName;
                }

                foreach ($addToCalendars as $calendarName) {
                    $calendar = $this->getCalendars()->get($calendarName)->get();
                    $calendar->getICalendar()->addEvent($event);
                }
            }
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
     * @param \LolEspCal\Retriever\LeaguesRetriever|null     $leaguesRetriever
     *
     * @return $this
     */
    public function setLeaguesRetriever(LeaguesRetriever $leaguesRetriever = null)
    {
        $this->leaguesRetriever = $leaguesRetriever;

        return $this;
    }

    /**
     * Get the leaguesRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\LeaguesRetriever
     */
    public function getLeaguesRetriever()
    {
        if (is_null($this->leaguesRetriever)) {
            $this->setLeaguesRetriever(new LeaguesRetriever());
        }

        return $this->leaguesRetriever;
    }

    /**
     * @param \LolEspCal\Retriever\SchedulesRetriever|null  $schedulesRetriever
     *
     * @return $this
     */
    public function setSchedulesRetriever(SchedulesRetriever $schedulesRetriever = null)
    {
        $this->schedulesRetriever = $schedulesRetriever;

        return $this;
    }

    /**
     * Get the schedulesRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\SchedulesRetriever
     */
    public function getSchedulesRetriever()
    {
        if (is_null($this->schedulesRetriever)) {
            $this->setSchedulesRetriever(new SchedulesRetriever());
        }

        return $this->schedulesRetriever;
    }

    /**
     * @param \LolEspCal\Retriever\TeamsRetriever|null  $teamsRetriever
     *
     * @return $this
     */
    public function setTeamsRetriever(TeamsRetriever $teamsRetriever = null)
    {
        $this->teamsRetriever = $teamsRetriever;

        return $this;
    }

    /**
     * Get the teamsRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\TeamsRetriever
     */
    public function getTeamsRetriever()
    {
        if (is_null($this->teamsRetriever)) {
            $this->setTeamsRetriever(new TeamsRetriever());
        }

        return $this->teamsRetriever;
    }

    /**
     * @param \LolEspCal\Retriever\PlayersRetriever|null  $playersRetriever
     *
     * @return $this
     */
    public function setPlayersRetriever(PlayersRetriever $playersRetriever = null)
    {
        $this->playersRetriever = $playersRetriever;

        return $this;
    }

    /**
     * Get the playersRetriever object. Will lazy load it if it isn't at 1st
     * call.
     *
     * @return \LolEspCal\Retriever\PlayersRetriever
     */
    public function getPlayersRetriever()
    {
        if (is_null($this->playersRetriever)) {
            $this->setPlayersRetriever(new PlayersRetriever());
        }

        return $this->playersRetriever;
    }

}
