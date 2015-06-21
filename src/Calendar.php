<?php

namespace LolEspCal;

use Eluceo\iCal\Component\Calendar as iCalendar;

/**
 * Description of Calendar
 *
 * @author Jeremy
 */
class Calendar
{
    /**
     * @var \Eluceo\iCal\Component\Calendar
     */
    private $iCalendar;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $filename;

    /**
     * @param \Eluceo\iCal\Component\Calendar   $iCalendar
     */
    public function __construct(iCalendar $iCalendar = null)
    {
        $this->setICalendar($iCalendar);
    }

    /**
     * @param \Eluceo\iCal\Component\Calendar   $iCalendar
     *
     * @return \LolEspCal\Calendar
     */
    public function setICalendar(iCalendar $iCalendar = null)
    {
        $this->iCalendar = $iCalendar;

        return $this;
    }

    /**
     * @return \Eluceo\iCal\Component\Calendar
     */
    public function getICalendar()
    {
        return $this->iCalendar;
    }

    /**
     * @param string    $type
     *
     * @return \LolEspCal\Calendar
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string    $name
     *
     * @return \LolEspCal\Calendar
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string    $filename
     *
     * @return \LolEspCal\Calendar
     */
    public function setFilename($filename = null)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
