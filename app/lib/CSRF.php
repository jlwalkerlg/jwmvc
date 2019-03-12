<?php

/**
 * CSRF class.
 */
class CSRF
{
    /** @var string $token CSRF token */
    private static $token;

    /**
     * Generate and store CSRF token on class and in session.
     *
     * @return string CSRF token.
     */
    public static function generateToken()
    {
        $token = md5(uniqid(rand(), true));
        Session::set('csrf_token', $token);
        Session::set('csrf_token_time', time());
        self::$token = $token;
        return $token;
    }


    /**
     * Generate CSRF token and return a hidden input field
     * containing the token for inclusion in a form.
     */
    public static function generateInput()
    {
        $token = self::$token ?? self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">' . "\n";
    }


    /**
     * Check CSRF token is in POST superglobal, matches that stored
     * in the session, and is not older than a day.
     */
    public static function validateToken()
    {
        if (!isset($_POST['csrf_token'])) {
            return false;
        }
        if (Session::get('csrf_token') !== $_POST['csrf_token']) {
            return false;
        }
        if (time() - Session::get('csrf_token_time') > 60*60*24) {
            return false;
        }
        return true;
    }
}
