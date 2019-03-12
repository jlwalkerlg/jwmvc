<?php

// Load bootstrap file.
require_once '../app/bootstrap.php';

// Load registered routes.
require_once APP_ROOT . '/routes/web.php';

// Route incoming URL.
Router::route();
