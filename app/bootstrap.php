<?php

// Load config file.
require_once 'config/config.php';

// Load helper functions.
require_once 'helpers/functions.php';

// Autoload vendor using composer.
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Autoload classes from lib or core directory.
function my_autoload($class) {
    if (file_exists(APP_ROOT . "/lib/{$class}.php")) {
        require_once APP_ROOT . "/lib/{$class}.php";
    } elseif (file_exists(APP_ROOT . "/core/{$class}.php")) {
        require_once APP_ROOT . "/core/{$class}.php";
    } elseif (file_exists(APP_ROOT . "/models/{$class}.php")) {
        require_once APP_ROOT . "/models/{$class}.php";
    }
}
spl_autoload_register('my_autoload');

// Start output buffering.
ob_start();

// Start session.
session_start();
