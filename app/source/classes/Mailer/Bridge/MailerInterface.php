<?php

namespace Postleaf\Mailer\Bridge;

use Postleaf\Mailer\Mail\Mail;

interface MailerInterface {

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function send(Mail $mail);

}
