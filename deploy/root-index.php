<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The Laravel application lives in the "sss" subfolder of this document root.
$appDir = __DIR__.'/sss';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appDir.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appDir.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
