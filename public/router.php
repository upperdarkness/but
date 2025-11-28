<?php
// Router script for PHP built-in server
// Routes all requests to index.php

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?? '/';

// If it's a file that exists, serve it directly
if ($requestPath !== '/' && file_exists(__DIR__ . $requestPath)) {
    return false;
}

// Otherwise, route to index.php
require __DIR__ . '/index.php';

