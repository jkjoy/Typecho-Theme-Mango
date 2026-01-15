<?php
// Typecho-Theme-Mango TimThumb hardening config.
// This file is automatically loaded by timthumb.php if present.

// Disable remote fetching (SSRF risk) and webshots.
define('ALLOW_EXTERNAL', false);
define('ALLOW_ALL_EXTERNAL_SITES', false);
define('WEBSHOT_ENABLED', false);
define('DISPLAY_ERROR_MESSAGES', false);

// Avoid being used as a public thumbnail service.
define('BLOCK_EXTERNAL_LEECHERS', true);

// Try to keep TimThumb strictly within the Typecho installation directory.
$typechoRoot = dirname(dirname(dirname(dirname(__FILE__))));
$typechoRoot = rtrim(str_replace('\\', '/', (string)$typechoRoot), '/');
define('LOCAL_FILE_BASE_DIRECTORY', $typechoRoot);

// Cache to Typecho cache directory (writable on most installs).
$cacheDir = $typechoRoot . '/usr/cache/timthumb';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
define('FILE_CACHE_DIRECTORY', $cacheDir);

// Reasonable defaults.
define('MAX_WIDTH', 1500);
define('MAX_HEIGHT', 1500);
define('DEFAULT_Q', 85);
define('DEFAULT_ZC', 1);
