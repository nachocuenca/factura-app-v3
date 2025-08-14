<?php
// Load environment variables from .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    foreach ($vars as $key => $value) {
        if (getenv($key) === false) {
            putenv("{$key}={$value}");
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST'));
define('DB_PORT', getenv('DB_PORT'));
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));
?>
