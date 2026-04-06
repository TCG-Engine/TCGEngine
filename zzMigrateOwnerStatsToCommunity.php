<?php
/**
 * zzMigrateOwnerStatsToCommunity.php
 *
 * Moves all source=1 (Owner) stats for a given deckID into source=0 (Community)
 * by adding the owner values into the community rows (INSERT ... ON DUPLICATE KEY UPDATE),
 * then deleting the owner rows from:
 *   - deckstats
 *   - carddeckstats
 *   - opponentdeckstats
 *
 * Usage (HTTP, mod login required):
 *   Dry run (no changes):  ?deckID=90523
 *   Execute migration:     ?deckID=90523&run=1
 *
 * The version column is preserved so versioned stats merge correctly per-version.
 * Everything runs inside a transaction — if any step fails, nothing is committed.
 */

include "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./Database/ConnectionManager.php";

$response = new stdClass();
$error = CheckLoggedInUserMod();
if ($error !== "") {
    $response->error = $error;
    echo json_encode($response);
    exit();
}

$deckID  = intval(TryGET("deckID", "0"));
$execute = TryGET("run", "0") === "1";

echo "<pre>";

if ($deckID <= 0) {
    echo "Usage: ?deckID=&lt;deckID&gt;          (dry run)\n";
    echo "       ?deckID=&lt;deckID&gt;&amp;run=1   (execute)\n";
    echo "\nExample for gameName=90523&amp;playerID=1&amp;folderPath=SWUDeck:\n";
    echo "  ?deckID=90523\n";
    echo "</pre>";
    exit();
}

$conn = GetLocalMySQLConnection();

// ── helpers ──────────────────────────────────────────────────────────────────

function countRows(mysqli $conn, string $table, int $deckID, int $source): int {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table` WHERE deckID = ? AND source = ?");
    $stmt->bind_param("ii", $deckID, $source);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    return (int)$cnt;
}

function runQuery(mysqli $conn, string $sql, array $params, string $types): int {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

// ── row-count preview ─────────────────────────────────────────────────────────

$ownerDeckStats     = countRows($conn, 'deckstats',         $deckID, 1);
$ownerCardStats     = countRows($conn, 'carddeckstats',     $deckID, 1);
$ownerOppStats      = countRows($conn, 'opponentdeckstats', $deckID, 1);
$commDeckStats      = countRows($conn, 'deckstats',         $deckID, 0);
$commCardStats      = countRows($conn, 'carddeckstats',     $deckID, 0);
$commOppStats       = countRows($conn, 'opponentdeckstats', $deckID, 0);

echo "=== zzMigrateOwnerStatsToCommunity | deckID=$deckID | " . ($execute ? "EXECUTE" : "DRY RUN") . " ===\n\n";
echo "Table                  source=1 (owner)   source=0 (community)\n";
echo "---------------------------------------------------------------\n";
echo sprintf("deckstats              %-18d %d\n", $ownerDeckStats,  $commDeckStats);
echo sprintf("carddeckstats          %-18d %d\n", $ownerCardStats,  $commCardStats);
echo sprintf("opponentdeckstats      %-18d %d\n", $ownerOppStats,   $commOppStats);
echo "\n";

if ($ownerDeckStats === 0 && $ownerCardStats === 0 && $ownerOppStats === 0) {
    echo "Nothing to migrate — no source=1 rows found for deckID=$deckID.\n";
    echo "</pre>";
    $conn->close();
    exit();
}

if (!$execute) {
    echo "Add &amp;run=1 to the URL to execute the migration.\n";
    echo "</pre>";
    $conn->close();
    exit();
}

// ── execute (transactional) ───────────────────────────────────────────────────

$conn->begin_transaction();

try {

    // ---- 1. deckstats -------------------------------------------------------
    $affected = runQuery($conn, "
        INSERT INTO deckstats
            (deckID, version, source,
             numWins, numPlays, playsGoingFirst,
             turnsInWins, totalTurns,
             cardsResourcedInWins, totalCardsResourced,
             remainingHealthInWins, winsGoingFirst, winsGoingSecond)
        SELECT
            deckID, version, 0,
            numWins, numPlays, playsGoingFirst,
            turnsInWins, totalTurns,
            cardsResourcedInWins, totalCardsResourced,
            remainingHealthInWins, winsGoingFirst, winsGoingSecond
        FROM deckstats
        WHERE deckID = ? AND source = 1
        ON DUPLICATE KEY UPDATE
            numWins               = numWins               + VALUES(numWins),
            numPlays              = numPlays              + VALUES(numPlays),
            playsGoingFirst       = playsGoingFirst       + VALUES(playsGoingFirst),
            turnsInWins           = turnsInWins           + VALUES(turnsInWins),
            totalTurns            = totalTurns            + VALUES(totalTurns),
            cardsResourcedInWins  = cardsResourcedInWins  + VALUES(cardsResourcedInWins),
            totalCardsResourced   = totalCardsResourced   + VALUES(totalCardsResourced),
            remainingHealthInWins = remainingHealthInWins + VALUES(remainingHealthInWins),
            winsGoingFirst        = winsGoingFirst        + VALUES(winsGoingFirst),
            winsGoingSecond       = winsGoingSecond       + VALUES(winsGoingSecond)
    ", [$deckID], "i");
    echo "deckstats — upserted: $affected rows affected\n";

    $affected = runQuery($conn, "DELETE FROM deckstats WHERE deckID = ? AND source = 1", [$deckID], "i");
    echo "deckstats — deleted (source=1): $affected rows\n\n";

    // ---- 2. carddeckstats ---------------------------------------------------
    $affected = runQuery($conn, "
        INSERT INTO carddeckstats
            (deckID, cardID, version, source,
             timesIncluded,   timesIncludedInWins,
             timesPlayed,     timesPlayedInWins,
             timesResourced,  timesResourcedInWins,
             timesDiscarded,  timesDiscardedInWins,
             timesDrawn,      timesDrawnInWins)
        SELECT
            deckID, cardID, version, 0,
            timesIncluded,   timesIncludedInWins,
            timesPlayed,     timesPlayedInWins,
            timesResourced,  timesResourcedInWins,
            timesDiscarded,  timesDiscardedInWins,
            timesDrawn,      timesDrawnInWins
        FROM carddeckstats
        WHERE deckID = ? AND source = 1
        ON DUPLICATE KEY UPDATE
            timesIncluded        = timesIncluded        + VALUES(timesIncluded),
            timesIncludedInWins  = timesIncludedInWins  + VALUES(timesIncludedInWins),
            timesPlayed          = timesPlayed          + VALUES(timesPlayed),
            timesPlayedInWins    = timesPlayedInWins    + VALUES(timesPlayedInWins),
            timesResourced       = timesResourced       + VALUES(timesResourced),
            timesResourcedInWins = timesResourcedInWins + VALUES(timesResourcedInWins),
            timesDiscarded       = timesDiscarded       + VALUES(timesDiscarded),
            timesDiscardedInWins = timesDiscardedInWins + VALUES(timesDiscardedInWins),
            timesDrawn           = timesDrawn           + VALUES(timesDrawn),
            timesDrawnInWins     = timesDrawnInWins     + VALUES(timesDrawnInWins)
    ", [$deckID], "i");
    echo "carddeckstats — upserted: $affected rows affected\n";

    $affected = runQuery($conn, "DELETE FROM carddeckstats WHERE deckID = ? AND source = 1", [$deckID], "i");
    echo "carddeckstats — deleted (source=1): $affected rows\n\n";

    // ---- 3. opponentdeckstats -----------------------------------------------
    $affected = runQuery($conn, "
        INSERT INTO opponentdeckstats
            (deckID, leaderID, version, source,
             winsVsGreen,     totalVsGreen,
             winsVsBlue,      totalVsBlue,
             winsVsRed,       totalVsRed,
             winsVsYellow,    totalVsYellow,
             winsVsColorless, totalVsColorless)
        SELECT
            deckID, leaderID, version, 0,
            winsVsGreen,     totalVsGreen,
            winsVsBlue,      totalVsBlue,
            winsVsRed,       totalVsRed,
            winsVsYellow,    totalVsYellow,
            winsVsColorless, totalVsColorless
        FROM opponentdeckstats
        WHERE deckID = ? AND source = 1
        ON DUPLICATE KEY UPDATE
            winsVsGreen      = winsVsGreen      + VALUES(winsVsGreen),
            totalVsGreen     = totalVsGreen     + VALUES(totalVsGreen),
            winsVsBlue       = winsVsBlue       + VALUES(winsVsBlue),
            totalVsBlue      = totalVsBlue      + VALUES(totalVsBlue),
            winsVsRed        = winsVsRed        + VALUES(winsVsRed),
            totalVsRed       = totalVsRed       + VALUES(totalVsRed),
            winsVsYellow     = winsVsYellow     + VALUES(winsVsYellow),
            totalVsYellow    = totalVsYellow    + VALUES(totalVsYellow),
            winsVsColorless  = winsVsColorless  + VALUES(winsVsColorless),
            totalVsColorless = totalVsColorless + VALUES(totalVsColorless)
    ", [$deckID], "i");
    echo "opponentdeckstats — upserted: $affected rows affected\n";

    $affected = runQuery($conn, "DELETE FROM opponentdeckstats WHERE deckID = ? AND source = 1", [$deckID], "i");
    echo "opponentdeckstats — deleted (source=1): $affected rows\n\n";

    $conn->commit();
    echo "=== Migration complete. Owner stats for deckID=$deckID merged into Community. ===\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR — transaction rolled back: " . htmlspecialchars($e->getMessage()) . "\n";
}

echo "</pre>";
$conn->close();
?>
