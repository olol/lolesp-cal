<?php

namespace LolEspCal\Event;

use Eluceo\iCal\Component\Event;
use LolEspCal\Schedule;

/**
 * Description of Generator
 *
 * @author Jeremy
 */
class Generator
{
    /**
     * @param array $match
     *
     * @return Event|null
     */
    public function createFromMatch($match)
    {
        // Wrong format !
        if (!is_array($match)) {
            return null;
        }

        $event = new Event();

        $blue = &$match['contestants']['blue'];
        $red = &$match['contestants']['red'];

        $description =
            $blue['name'] . ' (' . $blue['acronym'] . ') vs ' . $red['name'] . ' (' . $red['acronym'] . ')' . "\n" .
            'Type : BO' . $match['maxGames'] . "\n" .
            'Tournament : ' . $match['tournament']['name'] . "\n" .
            'Week : ' . $match['tournament']['round'] . "\n" .
            'Stream : ' . ($match['liveStreams'] == true ? 'Yes' : 'No') . "\n" .
            'Url : ' . 'http://www.lolesports.com' . $match['url'];

        $event
            ->setUniqueId('match-' . $match['matchId'])
            ->setSummary($match['name'])
            ->setUrl('http://www.lolesports.com' . $match['url'])
            ->setCategories([$match['tournament']['name']])
            ->setDescription($description)
            ->setDtStart(new \DateTime($match['dateTime']))
            ->setDtEnd((new \DateTime($match['dateTime']))->modify('+1 hour'))
        ;

        return $event;
    }

    /**
     * @param \LolEspCal\Event\Schedule $schedule
     *
     * @return Event|null
     */
    public function createFromSchedule(Schedule $schedule)
    {
        $event = new Event();

        $blue = &$schedule->getRosters()[0]['team'];
        $red = &$schedule->getRosters()[1]['team'];

        $description =
            $blue['name'] . ' (' . $blue['acronym'] . ') vs ' . $red['name'] . ' (' . $red['acronym'] . ')' . "\n" .
            'Type : BO' . count($schedule->getMatch()['games']) . "\n" .
            'Tournament : ' . $schedule->getTournament()['description'] . "\n" .
            'Week : ' . $schedule->getTags()['blockLabel'] . "\n"
        ;
        if (count($schedule->getMatch()['scores'])) {
            $blueScore = $schedule->getMatch()['scores'][$schedule->getRosters()[0]['id']];
            $redScore = $schedule->getMatch()['scores'][$schedule->getRosters()[1]['id']];
            $description .=
                'Winner : ' . ($blueScore > $redScore ? $blue['name'] : $red['name']) . "\n" .
                'Looser : ' . ($blueScore < $redScore ? $blue['name'] : $red['name']) . "\n"
            ;
        }

        // http://www.lolesports.com/en_US/eu-lcs/eu_2016_spring/schedule/regular_season/1
        $url =
            'http://www.lolesports.com/en_US/' .
            rawurlencode($schedule ->getLeague()['slug']) . '/' .
            rawurlencode($schedule->getTournament()['title']) . '/' .
            'schedule/' .
            rawurlencode($schedule->getBracket()['name']) . '/' .
            rawurlencode($schedule->getTags()['blockLabel'])
        ;

        $event
            ->setUniqueId('match-' . $schedule->getMatch()['id'])
            ->setSummary($blue['acronym'] . ' vs ' . $red['acronym'])
            ->setUrl($url)
            ->setCategories([$schedule->getTournament()['description']])
            ->setDescription($description)
            ->setDtStart(new \DateTime($schedule->getScheduledTime()->format('Y-m-dTH:i:s.000+0000')))
            ->setDtEnd((new \DateTime($schedule->getScheduledTime()->format('Y-m-dTH:i:s.000+0000')))->modify('+1 hour'))
        ;

        return $event;
    }
}
