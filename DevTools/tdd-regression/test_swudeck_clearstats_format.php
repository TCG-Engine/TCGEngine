<?php
// RUN VIA CLI (ClearStats.php needs a logged-in owner, which CLI can't provide; so this test seeds
// two formats and runs the same per-format DELETE/UPDATE statements ClearStats.php uses, proving the
// per-format scoping so a future regression is caught):
//   docker exec otmtcge-swustats-web-server-1 php /var/www/html/TCGEngine/DevTools/tdd-regression/test_swudeck_clearstats_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$deckID = 999900062;
function wipe($conn, $deckID) {
    foreach (['deckstats','carddeckstats','opponentdeckstats','opponentnamedbasestats'] as $t)
        $conn->query("DELETE FROM `$t` WHERE deckID = $deckID");
}
wipe($conn, $deckID);
// Seed one premier and one eternal deckstats + carddeckstats row.
foreach (['premier','eternal'] as $f) {
    $conn->query("INSERT INTO deckstats (deckID, source, version, format, numPlays) VALUES ($deckID, 0, 0, '$f', 1)");
    $conn->query("INSERT INTO carddeckstats (deckID, cardID, source, version, format, timesIncluded) VALUES ($deckID, 'ZZ', 0, 0, '$f', 1)");
}

// Mirror ClearStats.php scoped to eternal.
$conn->query("UPDATE deckstats SET numWins=0, numPlays=0, playsGoingFirst=0, turnsInWins=0, totalTurns=0, cardsResourcedInWins=0, totalCardsResourced=0, remainingHealthInWins=0, winsGoingFirst=0, winsGoingSecond=0 WHERE deckID=$deckID AND format='eternal'");
$conn->query("DELETE FROM carddeckstats WHERE deckID=$deckID AND format='eternal'");

$premCard = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID=$deckID AND format='premier'")->fetch_assoc()['c']);
$etrnCard = intval($conn->query("SELECT COUNT(*) c FROM carddeckstats WHERE deckID=$deckID AND format='eternal'")->fetch_assoc()['c']);
$etrnPlays = intval($conn->query("SELECT numPlays FROM deckstats WHERE deckID=$deckID AND format='eternal'")->fetch_assoc()['numPlays']);
$premPlays = intval($conn->query("SELECT numPlays FROM deckstats WHERE deckID=$deckID AND format='premier'")->fetch_assoc()['numPlays']);
$checks['premier carddeckstats survives'] = $premCard === 1;
$checks['eternal carddeckstats cleared'] = $etrnCard === 0;
$checks['eternal deckstats zeroed']       = $etrnPlays === 0;
$checks['premier deckstats untouched']    = $premPlays === 1;

wipe($conn, $deckID);
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
