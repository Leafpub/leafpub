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
    /**
     * @var Address
     */
    public Address $to;

    /**
     * @var Address
     */
    public Address $from;

    /**
     * @var string
     */
    public string $subject;

    /**
     * @var string
     */
    public string $message;

    /**
     * @param Address $to
     * @param Address $from
     * @param string $subject
     * @param string $message
     */
    public function __construct(
        Address $to,
        Address $from,
        string $subject,
        string $message
    )
    {
        $this->to = $to;
        $this->from = $from;
        $this->subject = $subject;
        $this->message = $message;
    }
}
