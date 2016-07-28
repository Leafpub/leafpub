<?php
//
// Postleaf\Session: methods for working with authenticated sessions
//
namespace Postleaf;

class Session extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Properties
    ////////////////////////////////////////////////////////////////////////////////////////////////

    private static $user;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private properties
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Generate and store an token
    private static function createJWT($username) {
        $now = time();
        $expires = $now + 3600; // + one hour

        $token = \Firebase\JWT\JWT::encode([
            'iat' => $now,
            'exp' => $expires,
            'data' => [
                'username' => $username
            ]
        ], Setting::get('auth_key'));

        // Save token in a cookie
        setcookie('authToken', $token, $expires, '/');
    }

    // Destroys the cookie holding the token
    private static function destroyJWT() {
        unset($_COOKIE['authToken']);
        setcookie('authToken', '', time() - 3600, '/');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public properties
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Initializes the user's session
    public static function init() {
        // No token means no session
        if(empty($_COOKIE['authToken'])) return false;

        // Decode the token
        try {
            \Firebase\JWT\JWT::$leeway = 60;
            $token = \Firebase\JWT\JWT::decode($_COOKIE['authToken'], Setting::get('auth_key'), ['HS256']);
        } catch(\Exception $e) {
            return false;
        }

        // Refresh expire time
        self::createJWT($token->data->username);

        // Fetch and store user data
        self::$user = User::get($token->data->username);

        return true;
    }

    // Determines is a user is currently logged in
    public static function isAuthenticated() {
        return isset(self::$user);
    }

    // Tests the authenticated user for a role. $role can be a string or an array of roles.
    public static function isRole($role) {
        return in_array(self::user('role'), (array) $role);
    }

    // Logs the user in and sets the token cookie
    public static function login($username, $password) {
        if(User::verifyPassword($username, $password)) {
            // Store user data
            self::$user = User::get($username);

            // Create the token
            self::createJWT($username);

            return true;
        } else {
            return false;
        }
    }

    // Logs the user out and destroys the token cookie
    public static function logout() {
        self::$user = null;
        self::destroyJWT();
        return true;
    }

    // Updates the authenticated user's token and data. This method should be called anytime the
    // authenticated user is updated. If the username (slug) has changed, pass it to $new_username.
    public static function update($new_username = null) {
        // Has the username (slug) changed?
        if($new_username !== Session::user()['slug']) {
            // Yep, update user data and token
            self::$user = User::get($new_username);
            self::createJWT($new_username);
        } else {
            // Nope, only update user data
            self::$user = User::get(self::$user['slug']);
        }
    }

    // Gets the user that is currently logged in. If $property is set, only that property will be
    // returned.
    public static function user($property = null) {
        if(self::isAuthenticated()) {
            return $property ? self::$user[$property] : self::$user;
        } else {
            return null;
        }
    }
}