<?php

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\OAuth;

// Alias the League Google OAuth2 provider class
use League\OAuth2\Client\Provider\Google;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Etc/UTC');

/**
 * Gmail class.
 *
 * Uses PHPMailer to send an Email via Gmail.
 * Taken from: https://github.com/PHPMailer/PHPMailer/blob/master/examples/gmail_xoauth.phps
 */
class Gmail
{
    // Fill in authentication details here.
    // Either the gmail account owner, or the user that gave consent.
    private static $email = GMAIL_ADDRESS;
    private static $clientId = GMAIL_CLIENT_ID;
    private static $clientSecret = GMAIL_CLIENT_SECRET;

    // Obtained by configuring and running get_oauth_token.php
    // after setting up an app in Google Developer Console.
    private static $refreshToken = GMAIL_REFRESH_TOKEN;

    private $mail;

    public function __construct()
    {
        // SMTP needs accurate times, and the PHP time zone MUST be set
        // This should be done in your php.ini, but this is how to do it if you don't have access to that
        date_default_timezone_set('Etc/UTC');

        // Create a new PHPMailer instance
        $this->mail = new PHPMailer;
        // Tell PHPMailer to use SMTP
        $this->mail->isSMTP();

        // Set the hostname of the mail server
        $this->mail->Host = 'smtp.gmail.com';

        // Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
        $this->mail->Port = 587;

        // Set the encryption system to use - ssl (deprecated) or tls
        $this->mail->SMTPSecure = 'tls';

        // Whether to use SMTP authentication
        $this->mail->SMTPAuth = true;

        // Set AuthType to use XOAUTH2
        $this->mail->AuthType = 'XOAUTH2';

        // Create a new OAuth2 provider instance
        $provider = new Google([
            'clientId' => self::$clientId,
            'clientSecret' => self::$clientSecret,
        ]);

        // Pass the OAuth provider instance to PHPMailer
        $this->mail->setOAuth(
            new OAuth([
                'provider' => $provider,
                'clientId' => self::$clientId,
                'clientSecret' => self::$clientSecret,
                'refreshToken' => self::$refreshToken,
                'userName' => self::$email,
            ])
        );

        // Set who the message is to be sent from
        // For gmail, this generally needs to be the same as the user you logged in as
        $this->mail->setFrom(self::$email, SITE_NAME);

        $this->mail->CharSet = 'utf-8';
    }

    public function compose($to, $subject, $msg, $altMsg=null)
    {
        // Set who the message is to be sent to
        $this->mail->addAddress($to);

        // Set the subject line
        $this->mail->Subject = $subject;

        // Read an HTML message body from an external file, convert referenced images to embedded,
        // convert HTML into a basic plain-text alternative body
        $this->mail->msgHTML($msg);
        // $this->mail->msgHTML(file_get_contents('contentsutf8.html'), __DIR__);

        if (isset($altMsg)) {
            // Replace the plain text body with one created manually
            $this->mail->AltBody = 'This is a plain-text message body';
        }
    }

    public function send()
    {
        return $this->mail->send();
    }
}
