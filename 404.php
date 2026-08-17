<?php
http_response_code(404);
$code = 404;
$title = 'Page not found';
$message = 'The page you requested could not be found.';
require_once __DIR__ . '/errors/404.php';
