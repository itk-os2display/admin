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
     * Get detailed information about a booking.
     *
     * @param $id
     *   The Exchange ID for the booking.
     * @param $changeKey
     *   The Exchange change key (revision id).
     *
     * @return array
     */
    public function getBooking($id, $changeKey)
    {
        // Build XML body.
        $body = implode(
            '',
            [
                '<GetItem xmlns="http://schemas.microsoft.com/exchange/services/2006/messages">',
                '<ItemShape>',
                '<t:BaseShape>Default</t:BaseShape>',
                '<t:BodyType>Text</t:BodyType>',
                '<t:AdditionalProperties>',
                '<t:FieldURI FieldURI="item:Body" />',
                '</t:AdditionalProperties>',
                '</ItemShape>',
                '<ItemIds>',
                '<t:ItemId Id="' . $id . '" ChangeKey="' . $changeKey . '"/>',
                '</ItemIds>',
                '</GetItem>',
            ]
        );

        $xml = $this->client->request('GetItem', $body);
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');
        $items = $xpath->query('//t:CalendarItem');

        if ($items->length) {
            return $this->nodeToArray($doc, $items->item(0));
        }

        return null;
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
            '<t:BaseShape>IdOnly</t:BaseShape>',
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
            $itemIds = $this->nodeToArray($doc, $calendarItem);

            $item = $this->getBooking($itemIds['ItemId']['@Id'], $itemIds['ItemId']['@ChangeKey']);

            $subject = array_key_exists('Subject', $item) ? $item['Subject'] : null;
            $location = array_key_exists('Location', $item) ? $item['Location'] : null;
            $startTime = array_key_exists('Start', $item) ? strtotime($item['Start']) : null;
            $endTime = array_key_exists('End', $item) ? strtotime($item['End']) : null;
            $body = array_key_exists('Body', $item) ? $item['Body'] : null;

            $booking = new ExchangeBooking();
            $booking->setEventName($subject);
            $booking->setLocation($location);
            $booking->setStartTime($startTime);
            $booking->setEndTime($endTime);
            $booking->setBody($body);

            $calendar->addBooking($booking);
        }

        return $calendar;
    }

    private function nodeToArray($dom, $node)
    {
        if (!is_a($dom, 'DOMDocument') || !is_a($node, 'DOMNode')) {
            return false;
        }
        $array = false;
        if (empty(trim($node->localName))) {// Discard empty nodes
            return false;
        }
        if (XML_TEXT_NODE == $node->nodeType) {
            return $node->nodeValue;
        }
        foreach ($node->attributes as $attr) {
            $array['@' . $attr->localName] = $attr->nodeValue;
        }
        foreach ($node->childNodes as $childNode) {
            if (1 == $childNode->childNodes->length && XML_TEXT_NODE == $childNode->firstChild->nodeType) {
                $array[$childNode->localName] = $childNode->nodeValue;
            } else {
                if (false !== ($a = self::nodeToArray($dom, $childNode))) {
                    $array[$childNode->localName] = $a;
                }
            }
        }
        return $array;
    }
}
