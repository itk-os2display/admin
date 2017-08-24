<?php
/**
 * @file
 *
 */

namespace Os2Display\ExchangeBundle\Model;

/**
 * Class ExchangeCalendar
 * @package Os2Display\ExchangeBundle\Model
 */
class ExchangeCalendar
{
    private $start;
    private $end;
    private $resourceMail;
    private $bookings;

    /**
     * @param $resourceMail
     * @param $start
     * @param $end
     * @param array $bookings
     */
    public function __construct($resourceMail, $start, $end, $bookings = array())
    {
        $this->resourceMail = $resourceMail;
        $this->start = $start;
        $this->end = $end;
        $this->bookings = $bookings;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param mixed $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return mixed
     */
    public function getResourceMail()
    {
        return $this->resourceMail;
    }

    /**
     * @param mixed $resourceMail
     */
    public function setResourceMail($resourceMail)
    {
        $this->resourceMail = $resourceMail;
    }

    /**
     * @return mixed
     */
    public function getBookings()
    {
        return $this->bookings;
    }

    /**
     * @param mixed $bookings
     */
    public function setBookings($bookings)
    {
        $this->bookings = $bookings;
    }

    /**
     * @param \Os2Display\ExchangeBundle\Model\ExchangeBooking $booking
     */
    public function addBooking(ExchangeBooking $booking)
    {
        $this->bookings[] = $booking;
    }
}
