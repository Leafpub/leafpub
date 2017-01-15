<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Mailer\Bridge;

use Postleaf\Mailer\Mail\Mail;

interface MailerInterface {

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function send(Mail $mail);

}
