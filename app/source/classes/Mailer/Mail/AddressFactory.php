<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Mailer\Mail;

use Leafpub\Mailer\MailerException;

class AddressFactory
{
    /**
     * @param string      $email
     * @param string|null $name
     *
     * @throws MailerException
     */
    public static function create(string $email, string $name = null): \Leafpub\Mailer\Mail\Address
    {
        // Allow email with local hostname and port number in dev mode
        if (!defined('LEAFPUB_DEV') && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MailerException('Invalid email: ' . $email);
        }

        return new Address($email, $name);
    }
}
