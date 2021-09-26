<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Mailer\Mail;

class Mail
{
    public string $to;

    public string $from;

    public string $subject;

    public string $message;

    /**
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $message
     */
    public function __construct($to, $from, $subject, $message)
    {
        $this->to = $to;
        $this->from = $from;
        $this->subject = $subject;
        $this->message = $message;
    }
}
