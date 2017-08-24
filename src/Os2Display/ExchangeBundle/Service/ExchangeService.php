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
        $calendar = $this->exchangeWebService->getResourceBookings($resourceMail, $startTime, $endTime);

        return $calendar->getBookings();
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
        $slides = $this->entityManager
            ->getRepository('IndholdskanalenMainBundle:Slide')->findBySlideType('calendar');
        $todayStart = time() - 3600;
        // Round down to nearest hour
        $todayStart = $todayStart - ($todayStart % 3600);

        $todayEnd = mktime(23, 59, 59);

        // Get data for interest period
        foreach ($slides as $slide) {
            $bookings = [];

            $options = $slide->getOptions();

            foreach ($options['resources'] as $resource) {
                $interestInterval = 0;
                // Read interestInterval from options.
                if (isset($options['interest_interval'])) {
                    $interestInterval = $options['interest_interval'];
                }
                $interestInterval = max(0, $interestInterval - 1) + 28;

                // Move today with number of requested days.
                $end = strtotime('+' . $interestInterval . ' days', $todayEnd);

                try {
                    $resourceBookings = $this->getExchangeBookingsForInterval($resource['mail'], $todayStart, $end);

                    if (count($resourceBookings) > 0) {
                        $bookings = array_merge($bookings, $resourceBookings);
                    }
                } catch (\Exception $e) {
                    // Ignore exceptions. The show must keep running, even though we have no connection to koba.
                }
            }


            // Sort bookings by start time.
            usort($bookings, function ($a, $b) {
                return strcmp($a->getStartTime(), $b->getStartTime());
            });


            // Save in externalData field
            $slide->setExternalData($bookings);

            $this->entityManager->flush();
        }
    }
}
