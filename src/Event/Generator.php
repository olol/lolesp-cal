<?php

namespace LolEspCal\Event;

use Eluceo\iCal\Component\Event;

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
}
