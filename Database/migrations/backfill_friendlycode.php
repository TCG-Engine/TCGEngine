<?php
// One-time backfill: assign a friendly code to every deck (assetType=1) missing one.
// Reuses a single connection and batches to stay well under any timeout. Idempotent + resumable
// (only touches rows where friendlyCode IS NULL). Run from the app root:
//   docker exec otmtcge-swustats-web-server-1 sh -lc 'cd /var/www/html/TCGEngine && php Database/migrations/backfill_friendlycode.php'
require_once __DIR__ . '/../ConnectionManager.php'; // Database/ConnectionManager.php
require_once __DIR__ . '/../../AccountFiles/AccountDatabaseAPI.php';

$conn = GetLocalMySQLConnection();
$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$done = 0; $fail = 0;

while (true) {
  $res = $conn->query("SELECT assetIdentifier FROM ownership WHERE assetType = 1 AND friendlyCode IS NULL LIMIT 500");
  if (!$res || $res->num_rows === 0) break;
  $ids = [];
  while ($row = $res->fetch_assoc()) { $ids[] = (int)$row['assetIdentifier']; }
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
    if ($assigned) { $done++; } else { $fail++; fwrite(STDERR, "FAILED id=$id\n"); }
  }
  echo "…assigned $done so far\n";
}
echo "DONE assigned=$done failed=$fail\n";
$conn->close();
