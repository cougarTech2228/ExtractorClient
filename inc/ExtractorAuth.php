<?php
/**
 * Created by PhpStorm.
 * User: zacharydubois
 * Date: 2018-02-2
 * Time: 20:40
 */

class ExtractorAuth
{
    /**
     * ExtractorAuth constructor.
     */
    public function __construct()
    {
        session_start([
            'name'            => 'scouting',
            'cookie_lifetime' => 300
        ]);
    }

    /**
     * ExtractorAuth destructor.
     */
    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Authenticate Password
     * Checks the password against the defined hash.
     *
     * @param string $pass Input password
     *
     * @return bool Auth success
     */
    public function auth($pass)
    {
        if (password_verify($pass, CONFIGPWDHASH)) {
            $_SESSION['check'] = true;

            return true;
        } else {
            $_SESSION['check'] = false;

            return false;
        }
    }

    /**
     * Deauth
     * De-authenticates the current session.
     */
    public function deauth()
    {
        $_SESSION['check'] = false;
    }

    /**
     * Check if user is authenticated.
     * Method checks if the current session is bearing an authentication token.
     * @return bool Auth status
     */
    public function isAuthed()
    {
        if (array_key_exists('check', $_SESSION)) {
            return $_SESSION['check'] ? true : false;
        }

        return false;
    }
}
