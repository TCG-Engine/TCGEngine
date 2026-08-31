<?php

// Local developer configuration shared by PHP generators and the MCP server. The file starts with
// executable PHP that returns 404, so the embedded JSON secret cannot be downloaded from the webroot.

const CARD_CODE_LOCAL_CONFIG_HEADER = "<?php http_response_code(404); exit; ?>\n";

function CardCodeLocalConfigPath(): string
{
    $override = trim((string)getenv('CARD_CODE_LOCAL_CONFIG_PATH'));
    return $override !== '' ? $override : __DIR__ . '/../../DevTools/local/card-code-connections.php';
}

function CardCodeLoadLocalConnections(): array
{
    $path = CardCodeLocalConfigPath();
    if (!is_file($path)) return [];
    $contents = file_get_contents($path);
    if ($contents === false || !str_starts_with($contents, CARD_CODE_LOCAL_CONFIG_HEADER)) {
        throw new RuntimeException('Local Card Code connection file is invalid');
    }
    $decoded = json_decode(substr($contents, strlen(CARD_CODE_LOCAL_CONFIG_HEADER)), true);
    if (!is_array($decoded)) throw new RuntimeException('Local Card Code connection file contains invalid JSON');
    $connections = $decoded['connections'] ?? [];
    return is_array($connections) ? $connections : [];
}

function CardCodeNormalizeConnection(string $rootName, array $connection): array
{
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $rootName)) throw new InvalidArgumentException('Invalid local app name');
    $url = rtrim(trim((string)($connection['url'] ?? '')), '/');
    $workspace = trim((string)($connection['workspace'] ?? $rootName));
    $token = trim((string)($connection['token'] ?? ''));
    if ($url === '' || $workspace === '' || $token === '') throw new InvalidArgumentException('Host URL, workspace, and token are required');
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $workspace)) throw new InvalidArgumentException('Invalid hosted workspace name');
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
        throw new InvalidArgumentException('The host URL must be a complete API URL without credentials, query parameters, or a fragment');
    }
    $scheme = strtolower((string)$parts['scheme']);
    $host = strtolower(trim((string)$parts['host'], '[]'));
    if ($scheme !== 'https' && !($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '::1'], true))) {
        throw new InvalidArgumentException('The host URL must use HTTPS; HTTP is allowed only for loopback');
    }
    if (strlen($url) > 2048 || strlen($token) > 512) throw new InvalidArgumentException('Card Code connection value is too long');
    return ['url' => $url, 'workspace' => $workspace, 'token' => $token];
}

function CardCodeSaveLocalConnections(array $connections): void
{
    $normalized = [];
    foreach ($connections as $rootName => $connection) {
        if (!is_array($connection)) continue;
        $normalized[(string)$rootName] = CardCodeNormalizeConnection((string)$rootName, $connection);
    }
    ksort($normalized, SORT_NATURAL | SORT_FLAG_CASE);
    $json = json_encode(['version' => 1, 'connections' => $normalized], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Could not encode local Card Code connections');
    $path = CardCodeLocalConfigPath();
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the local Card Code configuration directory');
    }
    if (file_put_contents($path, CARD_CODE_LOCAL_CONFIG_HEADER . $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Could not save the local Card Code connection');
    }
    @chmod($path, 0600);
}

function CardCodeConnectionMetadata(string $rootName, ?array $connection, ?string $source = 'local-file'): array
{
    if (!$connection) return ['root' => $rootName, 'configured' => false, 'source' => null];
    $token = trim((string)($connection['token'] ?? ''));
    return [
        'root' => $rootName,
        'configured' => true,
        'source' => $source,
        'url' => rtrim(trim((string)($connection['url'] ?? '')), '/'),
        'workspace' => trim((string)($connection['workspace'] ?? $rootName)),
        'tokenPrefix' => $token === '' ? null : substr($token, 0, 12) . '…',
    ];
}
