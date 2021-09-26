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

use Leafpub\Models\Setting;
use Leafpub\Models\User;

/**
 * Session
 *
 * methods for working with authenticated sessions
 *
 **/
class Session extends Leafpub
{
    /**
     * Properties
     **/
    private static $user;

    /**
     * Initializes the user's session
     *
     * @return bool
     *
     **/
    public static function init()
    {
        // No token means no session
        if (empty($_COOKIE['authToken'])) {
            return false;
        }

        // Decode the token
        try {
            \Firebase\JWT\JWT::$leeway = 60;
            $token = \Firebase\JWT\JWT::decode($_COOKIE['authToken'], Setting::getOne('auth_key'), ['HS256']);
        } catch (\Exception $e) {
            return false;
        }

        // Refresh expire time
        self::createJWT($token->data->username);

        // Fetch and store user data
        self::$user = User::getOne($token->data->username);

        return true;
    }

    /**
     * Determines is a user is currently logged in
     *
     * @return bool
     *
     **/
    public static function isAuthenticated()
    {
        return isset(self::$user);
    }

    /**
     * Tests the authenticated user for a role. $role can be a string or an array of roles.
     *
     * @param String/array $role
     *
     * @return bool
     *
     **/
    public static function isRole($role)
    {
        return in_array(self::user('role'), (array) $role);
    }

    /**
     * Logs the user in and sets the token cookie
     *
     * @param string $username
     * @param string $password
     *
     * @return bool
     *
     **/
    public static function login($username, $password)
    {
        if (User::verifyPassword($username, $password)) {
            // Store user data
            self::$user = User::getOne($username);

            // Create the token
            self::createJWT($username);

            return true;
        }

        return false;
    }

    /**
     * Logs the user out and destroys the token cookie
     *
     * @return bool
     *
     **/
    public static function logout()
    {
        self::$user = null;
        self::destroyJWT();

        return true;
    }

    /**
     * Updates the authenticated user's token and data. This method should be called anytime the
     * authenticated user is updated. If the username (slug) has changed, pass it to $new_username.
     *
     * @param null $new_username
     *
     * @return void
     *
     **/
    public static function update($new_username = null)
    {
        // Has the username (slug) changed?
        if ($new_username !== Session::user()['slug']) {
            // Yep, update user data and token
            self::$user = User::getOne($new_username);
            self::createJWT($new_username);
        } else {
            // Nope, only update user data
            self::$user = User::getOne(self::$user['slug']);
        }
    }

    /**
     * Gets the user that is currently logged in. If $property is set, only that property will be
     * returned.
     *
     * @param null $property
     *
     * @return mixed
     *
     **/
    public static function user($property = null)
    {
        if (self::isAuthenticated()) {
            return $property ? self::$user[$property] : self::$user;
        }

        return null;
    }

    /**
     * Generate and store an token
     *
     * @param string $username
     *
     * @return void
     *
     **/
    private static function createJWT($username)
    {
        $now = time();
        $expires = $now + 3600; // + one hour

        $token = \Firebase\JWT\JWT::encode([
            'iat' => $now,
            'exp' => $expires,
            'data' => [
                'username' => $username,
            ],
        ], Setting::getOne('auth_key'));

        // Save token in a cookie
        setcookie('authToken', $token, $expires, '/');
    }

    /**
     * Destroys the cookie holding the token
     *
     * @return void
     *
     **/
    private static function destroyJWT()
    {
        unset($_COOKIE['authToken']);
        setcookie('authToken', '', time() - 3600, '/');
    }
}
