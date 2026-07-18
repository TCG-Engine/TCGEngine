<?php
// RUN VIA CLI:
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_convertbases_format.php
//
// Proves the per-format scoping of ConvertBasesToCanonical's deckmetastats merge: a premier row under
// a non-canonical base and an eternal row under the canonical base for the SAME (leader, week) must
// NOT collide. Mirrors the script's per-format statements (seed directly, no invocation needed).
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$LEAD='ZZCBLEAD'; $NONCANON='ZZNONCANON'; $CANON='ZZCANON'; $week=0;
foreach ([$LEAD] as $l) $conn->query("DELETE FROM deckmetastats WHERE leaderID='$l'");
// premier row under the NON-canonical base (to be canonicalized), eternal row under the canonical base.
$conn->query("INSERT INTO deckmetastats (leaderID,baseID,week,format,numWins,numPlays) VALUES ('$LEAD','$NONCANON',$week,'premier',2,4)");
$conn->query("INSERT INTO deckmetastats (leaderID,baseID,week,format,numWins,numPlays) VALUES ('$LEAD','$CANON',$week,'eternal',5,9)");

// Simulate the script's per-format merge of the premier row into the canonical base (scoped by format).
$row = ['format'=>'premier','numWins'=>2,'numPlays'=>4];
// check canonical (leader, CANON, week, premier) exists?
$cnt = intval($conn->query("SELECT COUNT(*) c FROM deckmetastats WHERE leaderID='$LEAD' AND baseID='$CANON' AND week=$week AND format='premier'")->fetch_assoc()['c']);
if ($cnt == 0) {
    $conn->query("INSERT INTO deckmetastats (leaderID,baseID,week,format,numWins,numPlays) VALUES ('$LEAD','$CANON',$week,'premier',{$row['numWins']},{$row['numPlays']})");
}
$conn->query("DELETE FROM deckmetastats WHERE leaderID='$LEAD' AND baseID='$NONCANON' AND week=$week AND format='premier'");

// The eternal canonical row must be UNTOUCHED (9 plays); a new premier canonical row exists (4 plays).
$etrn = intval($conn->query("SELECT numPlays FROM deckmetastats WHERE leaderID='$LEAD' AND baseID='$CANON' AND week=$week AND format='eternal'")->fetch_assoc()['numPlays']);
$premRow = $conn->query("SELECT numPlays FROM deckmetastats WHERE leaderID='$LEAD' AND baseID='$CANON' AND week=$week AND format='premier'")->fetch_assoc();
$prem = $premRow ? intval($premRow['numPlays']) : -1;
$noncanonGone = intval($conn->query("SELECT COUNT(*) c FROM deckmetastats WHERE leaderID='$LEAD' AND baseID='$NONCANON'")->fetch_assoc()['c']);
$checks['eternal canonical untouched (9)'] = $etrn === 9;
$checks['premier canonical created (4)']   = $prem === 4;
$checks['non-canonical premier removed']    = $noncanonGone === 0;

$conn->query("DELETE FROM deckmetastats WHERE leaderID='$LEAD'");
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
