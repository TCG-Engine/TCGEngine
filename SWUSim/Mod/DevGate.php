<?php
// Local-dev gate for Mod tools that mutate source/assets (the cosmetics uploader writes to
// Catalog.php + the asset dirs). getenv('DEVENV') is only populated for CLI here (not php-fpm
// over HTTP), so also accept requests whose Host is localhost/loopback — that identifies the
// local dev environment over HTTP while still blocking production (swustats.net).
function SWUIsLocalDevRequest(): bool {
    if (getenv('DEVENV') === 'true') return true;
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return str_starts_with($host, 'localhost')
        || str_starts_with($host, '127.0.0.1')
        || str_starts_with($host, '[::1]');
}
