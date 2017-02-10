<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Mailer\Mail\Mail,
    Leafpub\Mailer\MailerException,
    Leafpub\Mailer\Bridge\MailerInterface;

class Mailer extends Leafpub {
    /**
     * @var array
     */
    static private $mailers = [
        'default' => [
            'name' => 'PHP mail',
            'class' => 'Leafpub\Mailer\Bridge\MailMailer'
        ]
    ];

    /**
     * @return array
     */
    static public function getMailers() {
        return self::$mailers;
    }

    /**
     * @param Mail $mail
     *
     * @return bool
     * @throws MailerException
     */
    static public function sendEmail(Mail $mail){
        $mailerClass = self::getMailerClass(Setting::getOne('mailer'));
        /** @var MailerInterface $mailer */
        $mailer = new $mailerClass();

        if (!($mailer instanceof MailerInterface)) {
            throw new MailerException("Mailer {$mailerClass} must implement MailerInterface");
        }

        return $mailer->send($mail);
    }

    /**
     * @param string $mailerName
     *
     * @throws MailerException
     */
    static private function getMailerClass($mailerName) {
        foreach(self::$mailers as $name => $mailer) {
            if ($mailerName === $name) {
                return $mailer['class'];
            }
        }
        throw new MailerException("Given mailer: '$mailerName' doesn't exist");
    }

    public static function addMailer($name, $class){
        if ($name === ''){
            throw new MailerException("Mailer name must be set!");
        }

        if ($class === ''){
            throw new MailerException("Mailer class must be set!");
        }

        self::$mailers[$name] = [
            'name' => $name,
            'class' => $class
        ];
    }

}
