<?php

// Fix open_basedir restriction for PHP file uploads & temporary files on web hosting
$tmpDir = __DIR__.'/../storage/app/tmp';
if (! is_dir($tmpDir)) {
    @mkdir($tmpDir, 0775, true);
}
putenv('TMPDIR='.$tmpDir);
putenv('TMP='.$tmpDir);
putenv('TEMP='.$tmpDir);
@ini_set('upload_tmp_dir', $tmpDir);
@ini_set('sys_temp_dir', $tmpDir);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Support/bcmath_polyfill.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
