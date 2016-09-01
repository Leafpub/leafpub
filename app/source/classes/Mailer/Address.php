<?php

namespace Postleaf\Mailer;

class Address
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string      $email
     * @param string|null $name
     *
     * @return Address
     * @throws MailerException
     */
    static public function create($email, $name = null)
    {

        if (!defined('POSTLEAF_DEV') && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MailerException("Invalid email: ".$email);
        }

        $address        = new Address();
        $address->email = $email;
        $address->name  = $name;

        return $address;
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFullAddress() {
        return "$this->name <$this->email>";
    }
}
