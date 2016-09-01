<?php

namespace Postleaf\Mailer;

class MailMailer implements MailerInterface
{

    /**
     * {@inheritdoc}
     */
    public function send(Mail $mail)
    {
        return mail(
            $mail->to->getFullAddress(),
            $mail->subject,
            $mail->message,
            'From: ' . $mail->from->getFullAddress()
        );
    }
}
