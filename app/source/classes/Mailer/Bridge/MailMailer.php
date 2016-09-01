<?php

namespace Postleaf\Mailer\Bridge;

use Postleaf\Mailer\Mail\Mail;

class MailMailer implements MailerInterface {

    /**
     * {@inheritdoc}
     */
    public function send(Mail $mail) {
        return mail(
            $mail->to->getFullAddress(),
            $mail->subject,
            $mail->message,
            'From: '.$mail->from->getFullAddress()
        );
    }
}
