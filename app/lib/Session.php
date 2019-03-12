<?php

/**
 * Session class.
 */
class Session
{
    /**
     * Get value stored in session.
     *
     * @param mixed $index Index of value to retrieve from session.
     * @return mixed The value, or null if not found.
     */
    public static function get($index)
    {
        return $_SESSION[$index] ?? null;
    }


    /**
     * Set a value in the session.
     *
     * @param mixed $index Index to store value under.
     * @param mixed $value Value to store under index in session.
     */
    public static function set($index, $value)
    {
        $_SESSION[$index] = $value;
    }


    /**
     * Unset a value from the session.
     *
     * @param mixed $index Index of value in session to unset.
     */
    public static function destroy($index)
    {
        unset($_SESSION[$index]);
    }


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
        self::set('user_id', $user->id);
        self::set('login_time', time());
    }


    /**
     * Log user out.
     *
     * Logs user out by deleting their ID from the
     * session, as well as their login time.
     */
    public static function logout()
    {
        self::destroy('user_id');
        self::destroy('login_time');
    }


    /**
     * Flash session message, or set message to be flashed.
     *
     * If called without any arguments, any flash message stored
     * in the session will be output as HTML then deleted from the session.
     * If called with arguments, a new flash message
     * will be stored in the session.
     *
     * @param string $type Type of message, e.g. success/error. Flash message has class flash-$type.
     * @param string $msg Flash message.
     */
    public static function flash(string $type = null, string $msg = null)
    {
        if (isset($type)) {
            self::set('flash', ['type' => $type, 'msg' => $msg]);
        } else {
            if ($flash = self::get('flash')) {
                echo '<p class="flash flash-' . $flash['type'] . '">' . h($flash['msg']) . '</p>';
                self::destroy('flash');
            }
        }
    }
}
