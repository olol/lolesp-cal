<?php

namespace LolEspCal;

use Eluceo\iCal\Component\Calendar as iCalendar;
use Eluceo\iCal\Component\Event as vEvent;

/**
 * Description of CalendarOld
 *
 * @author Jeremy
 */
class CalendarOld
{
    /**
     * Lol Esport api url to get what they refere to as blocks wich will then
     * lead to actual matches.
     *
     * @var string
     */
    private $apiBlocksUrl = 'http://euw.lolesports.com/api/programming.json?parameters[method]=all';

    /**
     * Lol Esport api url for a single matche.
     *
     * @var string
     */
    private $apiMatchUrl = 'http://euw.lolesports.com/api/match/{matchId}.json';

    /**
     * Make sure your web server (apache, nginx...) can write in there !
     *
     * @var string
     */
    private $cacheDir = 'cache/';

    /**
     * Date from wich to start getting matches.
     *
     * @var \DateTime
     */
    private $startDate;

    /**
     * Date from wich to stop getting matches.
     *
     * @var \DateTime
     */
    private $endDate;

    /**
     * A block looks like :
     *
{
    blockId: "1403",
    dateTime: "2014-02-23T20:00:00+00:00",
    tickets: "http://www.ticketweb.com/t3/sale/SaleEventDetail?dispatch=loadSelectionData&eventId=3985094&pl=riot",
    matches: [
        "1721",
        "1722",
        "1723",
        "1724"
    ],
    leagueId: "1",
    tournamentId: "33",
    tournamentName: "NA LCS Spring Split",
    significance: "0",
    tbdTime: "0",
    leagueColor: "#1376A4",
    week: "6",
    body: [
        {
            bodyTitle: null,
            body: null,
            bodyTime: "2014-07-07T23:15:00+00:00"
        }
    ],
    rebroadcastDate: "",
    label: "NA Spring Split - Week 6 Day 2"
},
     *
     * @var array of blocks
     */
    private $blocks;

    /**
     * A match looks like :
     *
{
    tournament: {
        id: "231",
        name: "2015 LPL Summer",
        round: "11"
    },
    url: "/tourney/match/5107",
    dateTime: "2015-08-09T05:00Z",
    winnerId: "",
    matchId: "5107",
    maxGames: "2",
    isLive: false,
    isFinished: "0",
    contestants: {
        blue: {
            id: "3748",
            name: "Snake",
            logoURL: "http://riot-web-cdn.s3-us-west-1.amazonaws.com/lolesports/s3fs-public/styles/grid_medium_square/public/snake-logo.png?itok=BqdIenkH",
            acronym: "SS",
            wins: 10,
            losses: 8
        },
        red: {
            id: "632",
            name: "Invictus Gaming",
            logoURL: "http://riot-web-cdn.s3-us-west-1.amazonaws.com/lolesports/s3fs-public/styles/grid_medium_square/public/iGlogo.png?itok=6OoFTsQ3",
            acronym: "iG",
            wins: 10,
            losses: 8
        }
    },
    liveStreams: false,
    polldaddyId: "8882387:40420917:40420918",
    games: {
        game0: {
            id: "6604",
            winnerId: "",
            noVods: 0,
            hasVod: 0
        }
    },
    name: "Snake vs Invictus Gaming"
}
     *
     * @var array of matches
     */
    private $matches;

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string    $baseUrl
     */
    public function __construct(\DateTime $startDate = null, \DateTime $endDate = null, $baseUrl = null)
    {
        if (is_null($startDate)) {
            $startDate = (new \DateTime())->modify('this week');
        }
        if (is_null($endDate)) {
            $endDate = (new \DateTime())->setISODate($startDate->format('Y'), $startDate->format('W'), 7);
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        if (!is_null($baseUrl)) {
            $this->baseUrl = $baseUrl;
        }


        $this->checkForCache();
    }

    /**
     * @param string $apiBlocksUrl
     */
    public function setApiBlocksUrl($apiBlocksUrl)
    {
        $this->apiBlocksUrl = $apiBlocksUrl;
    }

    /**
     * @return string
     */
    public function getApiBlocksUrl()
    {
        return $this->apiBlocksUrl;
    }

    /**
     * @param string $apiMatchUrl
     */
    public function setApiMatchUrl($apiMatchUrl)
    {
        $this->apiMatchUrl = $apiMatchUrl;
    }

    /**
     * @return string
     */
    public function getApiMatchUrl()
    {
        return $this->apiMatchUrl;
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param int   $blockId
     *
     * @return array|null
     */
    public function getBlock($blockId)
    {
        foreach ($this->blocks as &$block) {
            if ($block['blockId'] == $blockId) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Main access here !
     */
    public function generate()
    {
        $this->retrieveBlocks();
        $this->retrieveMatches();
        $this->createCalendar();
    }

    /**
     *
     */
    public function render()
    {
        if (!isset($this->calendar)) {
            throw new \Exception('Are you sure you generated the calendar first ?');
        }

        echo $this->calendar->render();
    }

    /**
     * @param string $filename
     */
    public function renderTo($filename)
    {
        if (!isset($this->calendar)) {
            throw new \Exception('Are you sure you generated the calendar first ?');
        }

        file_put_contents($filename, $this->calendar->render());
    }

    /**
     * @return string
     */
    private function getBlocksCacheFilename()
    {
        return $this->cacheDir . 'blocks-' . date('Y-m-d') . '.json';
    }

    /**
     * @return string
     */
    private function getMatchCacheFilename($matchId)
    {
        return $this->cacheDir . 'matches-' . $matchId . '-' . date('Y-m-d') . '.json';
    }

    /**
     *
     */
    private function checkForCache()
    {
        $blocksCacheFilename = $this->getBlocksCacheFilename();
        if (file_exists($blocksCacheFilename)) {
            $this->setApiBlocksUrl($blocksCacheFilename);
        }

//        $matchesCacheFilename = $this->getMatchesCacheFilename();
//        if (file_exists($matchesCacheFilename)) {
//            $this->setApiMatchUrl($matchesCacheFilename);
//        }
    }

    /**
     *
     */
    private function retrieveBlocks()
    {
        $url = $this->apiBlocksUrl;
        $content = file_get_contents($url);

        // Cache
        if (!is_file($this->getBlocksCacheFilename())) {
            file_put_contents($this->getBlocksCacheFilename(), $content);
        }

        $this->blocks = json_decode($content, true);
    }

    /**
     *
     */
    private function retrieveMatches()
    {
        $cpt = 0;
//        if (!is_file($this->getMatchesCacheFilename())) {
//            $handle = fopen($this->getMatchesCacheFilename(), 'w+');
//            fwrite($handle, '[');

            foreach ($this->blocks as $block) {
                if ($cpt == 50) {
                    break;
                }

                foreach ($block['matches'] as $matchId) {
                    $cache = false;
                    if (file_exists($this->getMatchCacheFilename($matchId))) {
                        $fileOrUrl = $this->getMatchCacheFilename($matchId);
                        $cache = true;
                    } else {
                        $fileOrUrl = $this->getMatchUrlByMatchId($matchId);
                    }
                    $content = file_get_contents($fileOrUrl);
//                    fwrite($handle, $content . ',');
                    $this->matches[] = json_decode($content, true);
                    if ($cache == false) {
                        file_put_contents($this->getMatchCacheFilename($matchId), $content);
                    }
                }

                $cpt++;
            }

//            fwrite($handle, ']');
//            echo json_encode($this->matches);
//            echo '<pre>';var_dump($this->matches);die;

//        } else {
//            $url = $this->getMatchUrlByMatchId();
//            $this->matches[] = json_decode(file_get_contents($url), true);
//            echo '<pre>';var_dump($this->matches);die;
//        }
    }

    /**
     * @param int $matchId
     *
     * @return string
     */
    private function getMatchUrlByMatchId($matchId = null)
    {
        return str_replace('{matchId}', $matchId, $this->apiMatchUrl);
    }

    /**
     *
     */
    private function createCalendar()
    {
        $this->calendar = new iCalendar('www.lolesport.com');

        foreach ($this->matches as $match) {
            $blue = &$match['contestants']['blue'];
            $red = &$match['contestants']['red'];
            $description =
                $blue['name'] . ' (' . $blue['acronym'] . ') vs ' . $red['name'] . ' (' . $red['acronym'] . ')' . "\n" .
                'Type : BO' . $match['maxGames'] . "\n" .
                'Tournament : ' . $match['tournament']['name'] . "\n" .
                'Week : ' . $match['tournament']['round'] . "\n" .
                'Stream : ' . ($match['liveStreams'] == true ? 'Yes' : 'No') .
                'Url : ' . 'http://www.lolesports.com' . $match['url'];
            $event = new vEvent();
            $event
                ->setUniqueId('match-' . $match['matchId'])
                ->setSummary($match['name'])
                ->setUrl('http://www.lolesports.com' . $match['url'])
                ->setCategories([$match['tournament']['name']])
                ->setDescription($description)
                ->setDtStart(new \DateTime($match['dateTime']))
                ->setDtEnd((new \DateTime($match['dateTime']))->modify('+1 hour'))
            ;
            $this->calendar->addComponent($event);
        }
    }

}
