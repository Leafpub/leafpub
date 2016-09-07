<?php

namespace Postleaf\Mailer\Mail;

class Mail {

    /**
     * @var Address
     */
    public $to;

    /**
     * @var Address
     */
    public $from;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $message;

    /**
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $message
     */
    public function __construct($to, $from, $subject, $message) {
        $this->to      = $to;
        $this->from    = $from;
        $this->subject = $subject;
        $this->message = $message;
    }
}
