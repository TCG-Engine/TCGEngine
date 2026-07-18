<?php
/**
 * zzBackfillFriendlyCode.php
 *
 * Assign a friendly share code (12-char [a-zA-Z]) to every deck (assetType=1)
 * that is missing one. Idempotent + resumable — only touches rows where
 * friendlyCode IS NULL, so it is safe to re-run.
 *
 * Runs under Apache (mod_php has mysqli; LAMPP's PHP CLI does not, hence a page
 * rather than a CLI script). Mod login required.
 *
 * Usage (mod login required):
 *   Dry run (counts only):  ?run=0  (or omit)
 *   Execute:                ?run=1   (auto-continues in ~55s batches until done)
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
@set_time_limit(0);

echo "<pre>";
echo "=== zzBackfillFriendlyCode.php ===\n";

$conn = GetLocalMySQLConnection();
if (!$conn) { echo "ERROR: Could not connect to database.\n</pre>"; exit(); }

$total     = (int) $conn->query("SELECT COUNT(*) AS c FROM ownership WHERE assetType = 1")->fetch_assoc()['c'];
$remaining = (int) $conn->query("SELECT COUNT(*) AS c FROM ownership WHERE assetType = 1 AND friendlyCode IS NULL")->fetch_assoc()['c'];
echo "Decks (assetType=1): {$total}\n";
echo "Missing a code:      {$remaining}\n";

if (!$execute) {
    echo "\nDry run. Re-run with ?run=1 to assign codes (auto-continues in batches).\n</pre>";
    $conn->close();
    exit();
}

$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // 52, no digits
$start  = microtime(true);
$budget = 55.0; // seconds/request -> stay under Cloudflare's ~100s cutoff
$done = 0; $fail = 0;

while (microtime(true) - $start < $budget) {
    $res = $conn->query("SELECT assetIdentifier FROM ownership WHERE assetType = 1 AND friendlyCode IS NULL LIMIT 500");
    if (!$res || $res->num_rows === 0) break;
    $ids = [];
    while ($row = $res->fetch_assoc()) { $ids[] = (int) $row['assetIdentifier']; }
    $res->free();

    foreach ($ids as $id) {
        $assigned = false;
        for ($attempt = 0; $attempt < 8 && !$assigned; $attempt++) {
            $code = '';
            for ($i = 0; $i < 12; $i++) { $code .= $alphabet[random_int(0, 51)]; }
            $u = $conn->prepare("UPDATE ownership SET friendlyCode = ? WHERE assetType = 1 AND assetIdentifier = ? AND friendlyCode IS NULL");
            $u->bind_param("si", $code, $id);
            try { $assigned = $u->execute() && $conn->affected_rows === 1; } catch (\mysqli_sql_exception $e) { $assigned = false; }
            $u->close();
        }
        if ($assigned) { $done++; } else { $fail++; }
    }
}

$remaining = (int) $conn->query("SELECT COUNT(*) AS c FROM ownership WHERE assetType = 1 AND friendlyCode IS NULL")->fetch_assoc()['c'];
$conn->close();

echo "\nThis run: assigned {$done}, failed {$fail}.\n";
echo "Remaining: {$remaining}\n";

if ($remaining > 0) {
    echo "\nMore to do — auto-continuing in 1s…\n</pre>";
    echo "<meta http-equiv='refresh' content='1;url=?run=1'>";
} else {
    echo "\nDONE — every deck has a friendly code.\n</pre>";
}
?>
