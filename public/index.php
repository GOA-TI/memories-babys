<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$samuelTmp = __DIR__.'/../storage';
if (is_dir($samuelTmp) && is_writable($samuelTmp)) {
    foreach (['TMPDIR', 'TEMP', 'TMP'] as $envKey) {
        putenv($envKey.'='.$samuelTmp);
        $_ENV[$envKey] = $samuelTmp;
        $_SERVER[$envKey] = $samuelTmp;
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
