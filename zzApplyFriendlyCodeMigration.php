<?php
/**
 * Adds the ownership.friendlyCode column and its unique index to the database
 * configured for this site.
 *
 * This endpoint is intentionally restricted to the OotTheMonk account. A GET
 * request only reports status; applying the migration requires the POST form
 * and its session-bound CSRF token.
 */

include_once __DIR__ . '/AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/Database/ConnectionManager.php';

CheckSession();

if (!IsUserLoggedIn() || !hash_equals('OotTheMonk', (string)LoggedInUserName())) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden: this migration may only be run by OotTheMonk.\n";
    exit();
}

if (!isset($_SESSION['friendly_code_migration_csrf'])) {
    $_SESSION['friendly_code_migration_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = (string)$_SESSION['friendly_code_migration_csrf'];
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($isPost) {
    $submittedToken = (string)($_POST['csrf'] ?? '');
    if ($submittedToken === '' || !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Invalid security token. Reload the page and try again.\n";
        exit();
    }
}

header('Content-Type: text/html; charset=utf-8');

function FriendlyCodeColumnExists(mysqli $conn): bool
{
    $sql = "SELECT COUNT(*) AS cnt
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ownership'
              AND COLUMN_NAME = 'friendlyCode'";
    $result = $conn->query($sql);
    if (!$result) throw new RuntimeException('Could not inspect ownership columns: ' . $conn->error);
    $exists = (int)($result->fetch_assoc()['cnt'] ?? 0) > 0;
    $result->free();
    return $exists;
}

function FriendlyCodeUniqueIndexExists(mysqli $conn): bool
{
    $sql = "SELECT COUNT(*) AS cnt
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'ownership'
              AND INDEX_NAME = 'idx_ownership_friendlyCode'
              AND COLUMN_NAME = 'friendlyCode'
              AND NON_UNIQUE = 0";
    $result = $conn->query($sql);
    if (!$result) throw new RuntimeException('Could not inspect ownership indexes: ' . $conn->error);
    $exists = (int)($result->fetch_assoc()['cnt'] ?? 0) > 0;
    $result->free();
    return $exists;
}

function Html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$messages = [];
$error = null;
$databaseName = '';

try {
    $conn = GetLocalMySQLConnection();
    if (!$conn) throw new RuntimeException('Could not connect to the configured database.');

    $dbResult = $conn->query('SELECT DATABASE() AS databaseName');
    if (!$dbResult) throw new RuntimeException('Could not identify the configured database: ' . $conn->error);
    $databaseName = (string)($dbResult->fetch_assoc()['databaseName'] ?? '');
    $dbResult->free();

    $columnExists = FriendlyCodeColumnExists($conn);
    $indexExists = FriendlyCodeUniqueIndexExists($conn);

    if ($isPost) {
        if (!$columnExists) {
            if (!$conn->query("ALTER TABLE `ownership` ADD COLUMN `friendlyCode` varchar(12) DEFAULT NULL")) {
                throw new RuntimeException('Could not add ownership.friendlyCode: ' . $conn->error);
            }
            $messages[] = 'Added ownership.friendlyCode.';
            $columnExists = true;
        } else {
            $messages[] = 'ownership.friendlyCode already exists; skipped.';
        }

        if (!$indexExists) {
            if (!$conn->query("ALTER TABLE `ownership` ADD UNIQUE INDEX `idx_ownership_friendlyCode` (`friendlyCode`)")) {
                throw new RuntimeException('Could not add the friendly-code unique index: ' . $conn->error);
            }
            $messages[] = 'Added idx_ownership_friendlyCode.';
            $indexExists = true;
        } else {
            $messages[] = 'idx_ownership_friendlyCode already exists; skipped.';
        }

        if (!FriendlyCodeColumnExists($conn) || !FriendlyCodeUniqueIndexExists($conn)) {
            throw new RuntimeException('Post-migration verification failed.');
        }

        $messages[] = 'Migration verified successfully.';
        unset($_SESSION['friendly_code_migration_csrf']);
    }

    $conn->close();
} catch (Throwable $throwable) {
    $error = $throwable->getMessage();
    if (isset($conn) && $conn instanceof mysqli) $conn->close();
    http_response_code(500);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Friendly-code database migration</title>
</head>
<body>
  <main>
    <h1>Friendly-code database migration</h1>
    <p>Configured database: <strong><?= Html($databaseName !== '' ? $databaseName : '(unknown)') ?></strong></p>

    <?php if ($error !== null): ?>
      <p><strong>Error:</strong> <?= Html($error) ?></p>
    <?php else: ?>
      <p>Column <code>ownership.friendlyCode</code>: <strong><?= $columnExists ? 'ready' : 'missing' ?></strong></p>
      <p>Unique index <code>idx_ownership_friendlyCode</code>: <strong><?= $indexExists ? 'ready' : 'missing' ?></strong></p>

      <?php foreach ($messages as $message): ?>
        <p><?= Html($message) ?></p>
      <?php endforeach; ?>

      <?php if (!$isPost && (!$columnExists || !$indexExists)): ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= Html($csrfToken) ?>">
          <button type="submit">Apply migration</button>
        </form>
      <?php elseif (!$isPost): ?>
        <p>No changes are necessary.</p>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
