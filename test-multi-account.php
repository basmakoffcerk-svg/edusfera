<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// test if MultiAccountService works without anything breaking
$request = Illuminate\Http\Request::create('/test-session', 'GET');
// Not easy to test full auth + session lifecycle without browser.
