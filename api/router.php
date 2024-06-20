<?php
// Check if the request is for a file which exists
if (php_sapi_name() === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

// Load the requested PHP file
require_once __DIR__ . '/index.php';
