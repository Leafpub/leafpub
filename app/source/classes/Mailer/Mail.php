<?php

namespace Postleaf\Mailer;

class Mail
{

    /**
     * @var Address
     */
    public $to;

    /**
     * @var Address
     */
    public $from;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var string
     */
    public $message;

    /**
     * @param array $mailVars
     *
     * @return Mail
     * @throws MailerException
     */
    static public function create(array $mailVars) {

        if (!($mailVars['to'] instanceof Address)) {
            throw new MailerException("Field 'to' must be an instance of " . Address::class);
        }
        if (!($mailVars['from'] instanceof Address)) {
            throw new MailerException("Field 'from' must be an instance of " . Address::class);
        }

        $mail = new Mail();
        $mail->to = $mailVars['to'];
        $mail->from = $mailVars['from'];
        $mail->subject = $mailVars['subject'];
        $mail->message = $mailVars['message'];

        return $mail;
    }

}
