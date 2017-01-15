<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
 
namespace Leafpub\Mailer\Mail;

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
