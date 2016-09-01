<?php

namespace Postleaf\Mailer\Mail;

use Postleaf\Mailer\MailerException;

class AddressFactory {

    /**
     * @param string      $email
     * @param string|null $name
     *
     * @return Address
     * @throws MailerException
     */
    static public function create($email, $name = null) {

        // Allow email with local hostname and port number in dev mode
        if ( ! defined('POSTLEAF_DEV') && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MailerException("Invalid email: ".$email);
        }

        return new Address($email, $name);
    }
}
