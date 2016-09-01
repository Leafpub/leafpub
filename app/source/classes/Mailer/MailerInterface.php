<?php

namespace Postleaf\Mailer;

interface MailerInterface
{

    /**
     * @param Mail $mail
     *
     * @return bool
     */
    public function send(Mail $mail);

}
