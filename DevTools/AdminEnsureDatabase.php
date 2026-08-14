<?php

// Admin endpoint behind the Generator Workspace "Database" panel.
//
// Creates this environment's database and the card_abilities table when they are missing. A docker
// environment gets both for free from the /docker-entrypoint-initdb.d hook on first boot, but only
// on FIRST boot and only under docker — a hand-built LAMPP box never runs it, which is why prod
// fataled with "Table '<db>.card_abilities' doesn't exist" on every card-ability query.
//
// It acts on the database this site is CONFIGURED for (MYSQL_DATABASE_NAME), not on a name derived
// from the selected app: each site can only reach its own MySQL server, and shared-database apps
// (HellbreakDeck runs on hellbreaksim) have no database of their own.

include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
include_once __DIR__ . '/../CardEditor/Database/CardAbilityDB.php';

header('Content-Type: application/json');

$authError = CheckLoggedInUserMod();
if ($authError !== '') {
    http_response_code(403);
    echo json_encode(['error' => $authError]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('Database setup requires POST');
    CheckSession();
    $sessionToken = isset($_SESSION['generator_admin_csrf']) ? (string)$_SESSION['generator_admin_csrf'] : '';
    $requestToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if ($sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
        throw new InvalidArgumentException('Invalid security token; reload the admin page and try again');
    }

    $hostname = getenv('MYSQL_SERVER_NAME') ?: 'localhost';
    $username = getenv('MYSQL_SERVER_USER_NAME') ?: 'root';
    $password = getenv('MYSQL_ROOT_PASSWORD') ?: '';
    $databaseName = getenv('MYSQL_DATABASE_NAME') ?: 'swuonline';

    // The name goes into a CREATE DATABASE statement, where it cannot be a bound parameter.
    if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
        throw new InvalidArgumentException('MYSQL_DATABASE_NAME is not a plain identifier; refusing to run DDL against it');
    }

    // Connect WITHOUT selecting a database — selecting one that does not exist fails the connect.
    $server = @mysqli_connect($hostname, $username, $password);
    if (!$server) throw new RuntimeException('Could not reach MySQL at ' . $hostname);

    $existing = mysqli_query($server, "SHOW DATABASES LIKE '" . mysqli_real_escape_string($server, $databaseName) . "'");
    $databaseExisted = $existing && mysqli_num_rows($existing) > 0;
    if ($existing) mysqli_free_result($existing);

    if (!$databaseExisted) {
        if (!mysqli_query($server, "CREATE DATABASE `$databaseName` DEFAULT CHARACTER SET utf8mb4")) {
            throw new RuntimeException('Could not create database ' . $databaseName . ': ' . mysqli_error($server));
        }
    }
    mysqli_close($server);

    $conn = @mysqli_connect($hostname, $username, $password, $databaseName);
    if (!$conn) throw new RuntimeException('Could not open database ' . $databaseName);

    $tableResult = mysqli_query($conn, "SHOW TABLES LIKE 'card_abilities'");
    $tableExisted = $tableResult && mysqli_num_rows($tableResult) > 0;
    if ($tableResult) mysqli_free_result($tableResult);

    // force: the operator pressed this button because the schema may be wrong, so a cached
    // "already checked" for this connection must not short-circuit the work.
    CardAbilityDB::EnsureSchema($conn, true);

    $tableResult = mysqli_query($conn, "SHOW TABLES LIKE 'card_abilities'");
    $tableReady = $tableResult && mysqli_num_rows($tableResult) > 0;
    if ($tableResult) mysqli_free_result($tableResult);
    if (!$tableReady) throw new RuntimeException('card_abilities is still missing after schema setup');

    $countResult = mysqli_query($conn, 'SELECT COUNT(*) AS total FROM card_abilities');
    $rowCount = $countResult ? (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0) : 0;
    if ($countResult) mysqli_free_result($countResult);
    mysqli_close($conn);

    echo json_encode([
        'success' => true,
        'database' => $databaseName,
        'host' => $hostname,
        'databaseCreated' => !$databaseExisted,
        'tableCreated' => !$tableExisted,
        'abilityRows' => $rowCount,
    ]);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(['error' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('AdminEnsureDatabase error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database setup failed']);
}
