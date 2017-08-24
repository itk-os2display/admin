<?php
/**
 * @file
 * Contains the Os2Display ExchangeService.
 */

namespace Os2Display\ExchangeBundle\Service;

use Os2Display\ExchangeBundle\Model\ExchangeBooking;
use Os2Display\ExchangeBundle\Model\ExchangeCalendar;

/**
 * Class ExchangeWebService
 * @package Os2Display\ExchangeBundle\Service
 */
class ExchangeWebService
{

    private $client;

    public function __construct(ExchangeSoapClientService $client)
    {
        $this->client = $client;
    }

    /**
     * Get bookings on a resource.
     *
     * @param $resource
     *   The resource to list.
     * @param $from
     *   Unix timestamp for the start date to query Exchange.
     * @param $to
     *   Unix timestamp for the end date to query Exchange.
     *
     * @return ExchangeCalendar
     *   Exchange calender with all bookings in the interval.
     */
    public function getResourceBookings($resource, $from, $to)
    {
        $calendar = new ExchangeCalendar($resource, $from, $to);

        // Build XML body.
        $body = implode('', [
            '<FindItem  Traversal="Shallow" xmlns="http://schemas.microsoft.com/exchange/services/2006/messages">',
            '<ItemShape>',
            '<t:BaseShape>Default</t:BaseShape>',
            '</ItemShape>',
            '<CalendarView StartDate="' . date('c', $from) . '" EndDate="' . date('c', $to) . '"/>',
            '<ParentFolderIds>',
            '<t:DistinguishedFolderId Id="calendar"/>',
            '</ParentFolderIds>',
            '</FindItem>',
        ]);

        // Send request to EWS.
        // To add impersonation: $xml = $this->client->request('FindItem', $body, $resource);
        $xml = $this->client->request('FindItem', $body);

        // Parse the response.
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');

        // Find the calendar items.
        $calendarItems = $xpath->query('//t:CalendarItem');

        foreach ($calendarItems as $calendarItem) {
            $calendar->addBooking($this->parseBookingXML($calendarItem, $xpath));
        }

        return $calendar;
    }

    /**
     * Parse DOMNode with calendarItem data.
     *
     * @param \DOMNode $calendarItem
     *   Node with calendar item data from XML.
     * @param \DOMXPath $xpath
     *   XPath.

     * @return ExchangeBooking
     *   The parsed Exchange booking object.
     */
    private function parseBookingXML(\DOMNode $calendarItem, \DOMXPath $xpath)
    {
        $booking = new ExchangeBooking();
        $booking->setEventName($xpath->evaluate('./t:Subject', $calendarItem)->item(0)->nodeValue);

        // Set timestamps.
        $booking->setStartTime(strtotime($xpath->evaluate('./t:Start', $calendarItem)->item(0)->nodeValue));
        $booking->setEndTime(strtotime($xpath->evaluate('./t:End', $calendarItem)->item(0)->nodeValue));

        return $booking;
    }
}
