<?php

namespace Os2Display\ExchangeBundle\Model;

class ExchangeBooking
{
    public $event_name;
    public $start_time;
    public $end_time;

    public function __construct($event_name = '', $start_time = 0, $end_time = 0)
    {
        $this->event_name = $event_name;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->event_name;
    }

    /**
     * @param string $event_name
     */
    public function setEventName($event_name)
    {
        $this->event_name = $event_name;
    }

    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @param int $start_time
     */
    public function setStartTime($start_time)
    {
        $this->start_time = $start_time;
    }

    /**
     * @return int
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @param int $end_time
     */
    public function setEndTime($end_time)
    {
        $this->end_time = $end_time;
    }
}
