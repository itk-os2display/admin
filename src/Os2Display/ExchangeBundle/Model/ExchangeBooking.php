<?php

namespace Os2Display\ExchangeBundle\Model;

class ExchangeBooking
{
    public $id;
    public $changeKey;
    public $subject;
    public $start;
    public $end;
    public $body;

    public function __construct($id, $changeKey, $subject = '', $start = 0, $end = 0, $body = null)
    {
        $this->id = $id;
        $this->changeKey = $changeKey;
        $this->subject = $subject;
        $this->start = $start;
        $this->end = $end;
        $this->body = $body;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param null $body
     *
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChangeKey()
    {
        return $this->changeKey;
    }

    /**
     * @param mixed $changeKey
     *
     * @return $this
     */
    public function setChangeKey($changeKey)
    {
        $this->changeKey = $changeKey;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param mixed $start
     *
     * @return $this
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
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
     *
     * @return $this
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }
}
