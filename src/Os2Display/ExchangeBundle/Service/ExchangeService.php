<?php
/**
 * @file
 * Wrapper service for the more specialized exchanges services.
 */

namespace Os2Display\ExchangeBundle\Service;

use Doctrine\ORM\EntityManager;
use Indholdskanalen\MainBundle\Events\CronEvent;

/**
 * Class ExchangeService
 * @package Os2Display\ExchangeBundle\Service
 */
class ExchangeService
{
    protected $exchangeWebService;
    protected $serviceEnabled;
    protected $entityManager;

    /**
     * ExchangeService constructor.
     *
     * @param ExchangeWebService $exchangeWebService
     * @param EntityManager $entityManager
     * @param $serviceEnabled
     */
    public function __construct(ExchangeWebService $exchangeWebService, EntityManager $entityManager, $serviceEnabled)
    {
        $this->exchangeWebService = $exchangeWebService;
        $this->serviceEnabled = $serviceEnabled;
        $this->entityManager = $entityManager;
    }

    /**
     * ik.onCron event listener.
     *
     * Updates calendar slides.
     *
     * @param CronEvent $event
     */
    public function onCron(CronEvent $event)
    {
        $this->updateCalendarSlides();
    }

    /**
     * Get bookings for a given resource.
     *
     * @param $resourceMail
     *   The mail of the resource.
     * @param int $from
     *   The the start of the interval as unix timestamp.
     * @param int $to
     *   The the end of the interval as unix timestamp.
     * @param bool $enrich
     *   Enrich the result with information from the bookings body.
     *
     * @return \Os2Display\ExchangeBundle\Model\ExchangeCalendar
     *   Exchange calendar object with bookings for the interval.
     */
    public function getResourceBookings($resourceMail, $from, $to, $enrich = true)
    {
        // Get basic calendar information.
        $calendar = $this->exchangeWebService->getResourceBookings($resourceMail, $from, $to);

        // Check if body information should be included.
        if ($enrich) {
            $bookings = $calendar->getBookings();
            foreach ($bookings as &$booking) {
                $booking = $this->exchangeWebService->getBooking(
                    $resourceMail,
                    $booking->getId(),
                    $booking->getChangeKey()
                );
            }
            $calendar->setBookings($bookings);
        }

        return $calendar;
    }

    /**
     * Get the ExchangeBookings for a resource in an interval.
     *
     * @param $resourceMail
     *   The resource mail.
     * @param $startTime
     *   The start time.
     * @param $endTime
     *   The end time.
     *
     * @return array
     *   Array of ExchangeBookings.
     */
    public function getExchangeBookingsForInterval($resourceMail, $startTime, $endTime)
    {
        // Start by getting the bookings from exchange.
        $exchangeCalendar = $this->getResourceBookings($resourceMail, $startTime, $endTime);
        return $exchangeCalendar->getBookings();
    }


    /**
     * Update the calendar events for calendar slides.
     */
    public function updateCalendarSlides()
    {
        // Only run if enabled.
        if (!$this->serviceEnabled) {
            return;
        }

        // For each calendar slide
        $slides = $this->container->get('doctrine')
            ->getRepository('IndholdskanalenMainBundle:Slide')->findBySlideType('calendar');
        $todayStart = time() - 3600;
        // Round down to nearest hour
        $todayStart = $todayStart - ($todayStart % 3600);

        $todayEnd = mktime(23, 59, 59);

        // Get data for interest period
        foreach ($slides as $slide) {
            $bookings = array();

            $options = $slide->getOptions();

            foreach ($options['resources'] as $resource) {
                $interestInterval = 0;
                // Read interestInterval from options.
                if (isset($options['interest_interval'])) {
                    $interestInterval = $options['interest_interval'];
                }
                $interestInterval = max(0, $interestInterval - 1);

                // Move today with number of requested days.
                $end = strtotime('+' . $interestInterval . ' days', $todayEnd);

                try {
                    $resourceBookings = $this->getResourceBookings($resource['mail'], $todayStart, $end);

                    if (count($resourceBookings->getBookings()) > 0) {
                        $bookings = array_merge($bookings, $resourceBookings);
                    }
                } catch (\Exception $e) {
                    // Ignore exceptions. The show must keep running, even though we have no connection to koba.
                }
            }

            // Sort bookings by start time.
            usort($bookings, function ($a, $b) {
                return strcmp($a->start_time, $b->start_time);
            });

            // Save in externalData field
            $slide->setExternalData($bookings);

            $this->entityManager->flush();
        }
    }
}
