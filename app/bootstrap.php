<?php

// Load config file.
require_once 'config/config.php';

// Load helper functions.
require_once 'helpers/functions.php';

// Autoload vendor using composer.
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Autoload classes from lib or core directory.
function my_autoload($class) {
    $path = namespaceToPath($class);
    if (file_exists(APP_ROOT . "/lib/{$path}.php")) {
        require_once APP_ROOT . "/lib/{$path}.php";
    } elseif (file_exists(APP_ROOT . "/core/{$path}.php")) {
        require_once APP_ROOT . "/core/{$path}.php";
    } elseif (file_exists(APP_ROOT . "/models/{$path}.php")) {
        require_once APP_ROOT . "/models/{$path}.php";
    }
}
spl_autoload_register('my_autoload');

// Start output buffering.
ob_start();

// Start session.
session_start();
