<?php

namespace Os2Display\ExchangeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DefaultController
 * @package Os2Display\ExchangeBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * Test controller action.
     *
     * @param $email
     *   The email of the resource to get calendar data from.
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexAction($email)
    {
        $start = mktime(0, 0, 0);
        $end = strtotime('+7 days', mktime(23, 59, 29));

        $calendar = $this->get('os2display.exchange_service')
            ->getExchangeBookingsForInterval(
                $email,
                $start,
                $end
            );

        return new JsonResponse($calendar);
    }
}
