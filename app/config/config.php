<?php

// DB credentials.
define('DB_HOST', getenv('DB_HOST')); // use IP address to avoid slow DNS lookup
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', getenv('DB_NAME'));

// URL root
define('URL_ROOT', '/'); // Set this to / for a live app.

// App root.
define('APP_ROOT', dirname(dirname(__FILE__)));

// Project root.
define('PROJECT_ROOT', dirname(APP_ROOT));

// Public root.
define('PUBLIC_ROOT', PROJECT_ROOT . '/public');

// Site name.
define('SITE_NAME', 'SITE_NAME');

// Gmail credentials.
define('GMAIL_ADDRESS', getenv('GMAIL_ADDRESS'));
define('GMAIL_CLIENT_ID', getenv('GMAIL_CLIENT_ID'));
define('GMAIL_CLIENT_SECRET', getenv('GMAIL_CLIENT_SECRET'));
define('GMAIL_REFRESH_TOKEN', getenv('GMAIL_REFRESH_TOKEN'));
