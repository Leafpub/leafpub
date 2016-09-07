<?php

namespace Postleaf\Mailer\Mail;

class Address {
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
     */
    public function __construct($email, $name) {
        $this->email = $email;
        $this->name  = $name;
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
        return "{$this->name} <{$this->email}>";
    }
}
