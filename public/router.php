<?php

// PHP built-in dev server router script.
//
// The PHP built-in server (`php -S host:port -t public`) normally tries to
// serve any URL that resolves to an existing file under the document root.
// When a URL like /foo.json is requested and no file by that name exists,
// it returns its own 404 BEFORE CodeIgniter's index.php gets a chance to
// route the request. That breaks endpoints like
// /consultations/queue/state.json.
//
// This script is passed as the final arg to `php -S` so the server asks us
// how to handle each request. We return false to let the server serve a
// static file directly, and otherwise route everything through index.php.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$full = __DIR__ . $path;

// Serve real static assets (CSS, JS, images, fonts, etc.) directly.
if ($path !== '/' && is_file($full)) {
    return false;
}

// Everything else → front controller.
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
require __DIR__ . '/index.php';