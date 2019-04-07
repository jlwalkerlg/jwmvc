<?php

// Autoload vendor packages using composer.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load environment variables.
$dotenv = Dotenv\Dotenv::create(dirname(__DIR__));
$dotenv->load();

// Load config file.
require_once 'config/config.php';

// Load helper functions.
require_once 'helpers/functions.php';

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
