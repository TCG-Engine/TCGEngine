<?php
/**
 * zzUpdateOwnershipSchema.php
 *
 * Adds a `lastUpdated` TIMESTAMP column to the shared `ownership` table.
 * Applies to all asset types (SWUDeck, SoulMastersDB, etc.) — they all
 * share the same table. Existing rows default to 2001-12-12 00:00:00.
 *
 * Usage (mod login required):
 *   Dry run (no changes):  ?run=0  (or omit run)
 *   Execute migration:     ?run=1
 */

include "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./Database/ConnectionManager.php";

$error = CheckLoggedInUserMod();
if ($error !== "") {
    echo json_encode(["error" => $error]);
    exit();
}

$execute = TryGET("run", "0") === "1";

echo "<pre>";
echo "=== zzUpdateOwnershipSchema.php ===\n";
echo "Mode: " . ($execute ? "EXECUTE" : "DRY RUN (pass ?run=1 to apply)") . "\n\n";

$conn = GetLocalMySQLConnection();
if (!$conn) {
    echo "ERROR: Could not connect to database.\n</pre>";
    exit();
}

// ── Check whether the column already exists ───────────────────────────────────
$colCheckSql = "SELECT COUNT(*) AS cnt
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = 'ownership'
                  AND COLUMN_NAME  = 'lastUpdated'";
$result = $conn->query($colCheckSql);
$row    = $result->fetch_assoc();
$exists = (int)$row['cnt'] > 0;

if ($exists) {
    echo "Column `ownership`.`lastUpdated` already exists — nothing to do.\n</pre>";
    $conn->close();
    exit();
}

echo "Column `ownership`.`lastUpdated` does NOT exist.\n";

if (!$execute) {
    echo "\nWould run:\n";
    echo "  ALTER TABLE `ownership`\n";
    echo "    ADD COLUMN `lastUpdated` TIMESTAMP NOT NULL DEFAULT '2001-12-12 00:00:00';\n";
    echo "\nRe-run with ?run=1 to apply.\n</pre>";
    $conn->close();
    exit();
}

// ── Apply the migration ───────────────────────────────────────────────────────
$alterSql = "ALTER TABLE `ownership`
             ADD COLUMN `lastUpdated` TIMESTAMP NOT NULL DEFAULT '2001-12-12 00:00:00'";

if ($conn->query($alterSql) === TRUE) {
    echo "SUCCESS: Column `lastUpdated` added to `ownership`.\n";
    echo "  Default for all existing rows: 2001-12-12 00:00:00\n";

    // Confirm
    $result  = $conn->query($colCheckSql);
    $row     = $result->fetch_assoc();
    $nowExists = (int)$row['cnt'] > 0;
    echo "\nVerification: column " . ($nowExists ? "EXISTS ✓" : "MISSING ✗ (something went wrong)") . "\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

$conn->close();
echo "</pre>";
?>
