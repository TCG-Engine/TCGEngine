<?php

// Browser-only token management. MCP credentials never impersonate browser sessions.
require_once __DIR__ . '/../../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/Tokens.php';
chdir(dirname(__DIR__, 2));
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; form-action \'self\'; frame-ancestors \'none\'; base-uri \'none\'');
if (!CardEditingSecureRequest($_SERVER)) {
    http_response_code(403);
    echo 'HTTPS is required to manage remote card editing tokens.';
    exit;
}
if (!IsUserLoggedIn() || (int)LoggedInUser() <= 0) {
    http_response_code(403);
    echo 'Sign in to TCGEngine, then return here to manage card editing MCP tokens.';
    exit;
}
$userId = (int)LoggedInUser();
if (empty($_SESSION['cardEditingMcpCsrf'])) $_SESSION['cardEditingMcpCsrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['cardEditingMcpCsrf'];
$error = '';
$created = null;
$rows = [];
$conn = null;
try {
    $conn = GetLocalMySQLConnection();
    $tokens = new CardEditingTokens($conn);
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            http_response_code(403);
            throw new InvalidArgumentException('Invalid form token. Reload this page.');
        }
        if (($_POST['action'] ?? '') === 'create') {
            $created = $tokens->create($userId, (string)($_POST['name'] ?? ''), (string)($_POST['scope'] ?? ''), (int)($_POST['days'] ?? 0));
        } elseif (($_POST['action'] ?? '') === 'revoke') {
            $tokens->revoke($userId, (int)($_POST['id'] ?? 0));
        } else throw new InvalidArgumentException('Unknown token action');
    }
    $rows = $tokens->listForUser($userId);
} catch (InvalidArgumentException $e) { $error = $e->getMessage(); }
catch (Throwable $e) {
    error_log('Card editing token management failed: ' . $e->getMessage());
    http_response_code(503);
    $error = 'Token service unavailable. Check server logs.';
} finally { if ($conn) mysqli_close($conn); }
function ce_mcp_escape($value) { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Card editing MCP access</title>
<style>body{font:16px system-ui;max-width:1000px;margin:40px auto;padding:0 20px;background:#202124;color:#eee}a{color:#8bd}input,select,button{font:inherit;margin:6px;padding:8px}table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #666;padding:10px}code{overflow-wrap:anywhere}.secret{border:1px solid #8bd;padding:16px}label{display:inline-block}</style></head><body>
<h1>Card editing MCP access</h1><p><a href="../UI/index.html">Return to card editor</a></p>
<p>Tokens act as your account and retain your game ownership and team permissions. Read tokens can inspect content. Write tokens can create, edit, upload, and delete content you can edit. They do not grant simulator programming access.</p>
<p>MCP endpoint: <code>CardEditor/Mcp/index.php</code> on this host. Use HTTPS for remote connections.</p>
<?php if ($error !== ''): ?><p role="alert"><?= ce_mcp_escape($error) ?></p><?php endif; ?>
<?php if ($created): ?><div class="secret"><p>Copy this token now; it is only shown once. Store it in your client’s secret environment. Expires <?= ce_mcp_escape($created['expiresAt']) ?> UTC.</p><code><?= ce_mcp_escape($created['token']) ?></code></div><?php endif; ?>
<h2>Create a token</h2>
<form method="post"><input type="hidden" name="csrf" value="<?= ce_mcp_escape($csrf) ?>"><input type="hidden" name="action" value="create">
<label>Name <input name="name" required maxlength="100" placeholder="My Codex"></label>
<label>Access <select name="scope"><option value="read">Read</option><option value="write">Read and write</option></select></label>
<label>Days <input type="number" name="days" min="1" max="365" value="90" required></label><button>Create token</button></form>
<h2>Your tokens</h2><table><thead><tr><th>Name</th><th>Access</th><th>Expires (UTC)</th><th>Last used (UTC)</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= ce_mcp_escape($row['name']) ?></td><td><?= ce_mcp_escape($row['scope']) ?></td><td><?= ce_mcp_escape($row['expires_at']) ?></td><td><?= ce_mcp_escape($row['last_used_at'] ?? 'Never') ?></td><td>
<?php if ($row['revoked_at']): ?>Revoked<?php elseif ($row['expires_at'] <= gmdate('Y-m-d H:i:s')): ?>Expired<?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?= ce_mcp_escape($csrf) ?>"><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button>Revoke</button></form>
<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></body></html>
