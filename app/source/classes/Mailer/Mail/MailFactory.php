<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2017 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Mailer\Mail;

use Leafpub\Mailer\MailerException;

class MailFactory {

    /**
     * @param array $mailVars
     *
     * @return Mail
     * @throws MailerException
     */
    static public function create(array $mailVars) {

        if ( ! ($mailVars['to'] instanceof Address)) {
            throw new MailerException("Field 'to' must be an instance of " . Address::class);
        }
        if ( ! ($mailVars['from'] instanceof Address)) {
            throw new MailerException("Field 'from' must be an instance of " . Address::class);
        }

        return new Mail(
            $mailVars['to'],
            $mailVars['from'],
            $mailVars['subject'],
            $mailVars['message']
        );
    }
}
