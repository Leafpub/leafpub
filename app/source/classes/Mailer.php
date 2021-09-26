<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Mailer\Bridge\MailerInterface;
use Leafpub\Mailer\Mail\Mail;
use Leafpub\Mailer\MailerException;

class Mailer extends Leafpub
{
    private static array $mailers = [
        'default' => [
            'name' => 'PHP mail',
            'class' => 'Leafpub\Mailer\Bridge\MailMailer',
        ],
    ];

    /**
     * @return array
     */
    public static function getMailers()
    {
        return self::$mailers;
    }

    /**
     * @throws MailerException
     *
     * @return bool
     */
    public static function sendEmail(Mail $mail)
    {
        $mailerClass = self::getMailerClass(Setting::getOne('mailer'));
        /** @var MailerInterface $mailer */
        $mailer = new $mailerClass();

        if (!($mailer instanceof MailerInterface)) {
            throw new MailerException("Mailer {$mailerClass} must implement MailerInterface");
        }

        return $mailer->send($mail);
    }

    public static function addMailer($name, $class)
    {
        if ($name === '') {
            throw new MailerException('Mailer name must be set!');
        }

        if ($class === '') {
            throw new MailerException('Mailer class must be set!');
        }

        self::$mailers[$name] = [
            'name' => $name,
            'class' => $class,
        ];
    }

    /**
     * @param string $mailerName
     *
     * @throws MailerException
     */
    private static function getMailerClass($mailerName)
    {
        foreach (self::$mailers as $name => $mailer) {
            if ($mailerName === $name) {
                return $mailer['class'];
            }
        }
        throw new MailerException("Given mailer: '$mailerName' doesn't exist");
    }
}
