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

class Address
{
    /**
     * @var string
     */
    private string $email;

    /**
     * @var null|string
     */
    private ?string $name = null;

    /**
     * @param string      $email
     * @param string|null $name
     */
    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFullAddress(): string
    {
        return "{$this->name} <{$this->email}>";
    }
}
