<?php

namespace Postleaf\Mailer\Mail;

use Postleaf\Mailer\MailerException;

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
