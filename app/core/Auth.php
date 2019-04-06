<?php

/**
 * Core Auth class.
 * - login/logout
 * - check logged in status
 * - store logged in user instance
 * - provide authenication middleware
 */
class Auth
{
    /** @var User $user Current logged in user instance. */
    private static $user;


    /**
     * Check if user is logged in.
     *
     * @return bool True if user is logged in; false otherwise.
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }


    /**
     * Log user in.
     *
     * Regenerates session ID, then logs user in by
     * setting their ID and the login time in the session.
     *
     * @param object $user User object.
     */
    public static function login(object $user)
    {
        session_regenerate_id();
        $_SESSION['user_id'] = $user->id;
        $_SESSION['login_time'] = time();
    }


    /**
     * Log user out.
     *
     * Logs user out by deleting their ID from the
     * session, as well as their login time.
     */
    public static function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['login_time']);
    }


    /**
     * Retrieve logged in user.
     */
    public static function user()
    {
        if (!self::isLoggedIn()) return false;
        if (!isset(self::$user) || !(self::$user instanceof User)) {
            self::$user = User::find($_SESSION['user_id']);
        }
        return self::$user;
    }
}
