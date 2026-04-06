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
    // Merge into existing community rows via self-join UPDATE
    $affected = runQuery($conn, "
        UPDATE deckstats AS comm
        JOIN deckstats AS owner
            ON owner.deckID = comm.deckID AND owner.version = comm.version AND owner.source = 1
        SET comm.numWins               = comm.numWins               + owner.numWins,
            comm.numPlays              = comm.numPlays              + owner.numPlays,
            comm.playsGoingFirst       = comm.playsGoingFirst       + owner.playsGoingFirst,
            comm.turnsInWins           = comm.turnsInWins           + owner.turnsInWins,
            comm.totalTurns            = comm.totalTurns            + owner.totalTurns,
            comm.cardsResourcedInWins  = comm.cardsResourcedInWins  + owner.cardsResourcedInWins,
            comm.totalCardsResourced   = comm.totalCardsResourced   + owner.totalCardsResourced,
            comm.remainingHealthInWins = comm.remainingHealthInWins + owner.remainingHealthInWins,
            comm.winsGoingFirst        = comm.winsGoingFirst        + owner.winsGoingFirst,
            comm.winsGoingSecond       = comm.winsGoingSecond       + owner.winsGoingSecond
        WHERE comm.deckID = ? AND comm.source = 0
    ", [$deckID], "i");
    echo "deckstats — merged into existing community rows: $affected\n";

    // Insert community rows for versions that have no community entry yet
    $affected = runQuery($conn, "
        INSERT INTO deckstats
            (deckID, version, source,
             numWins, numPlays, playsGoingFirst,
             turnsInWins, totalTurns,
             cardsResourcedInWins, totalCardsResourced,
             remainingHealthInWins, winsGoingFirst, winsGoingSecond)
        SELECT s.deckID, s.version, 0,
               s.numWins, s.numPlays, s.playsGoingFirst,
               s.turnsInWins, s.totalTurns,
               s.cardsResourcedInWins, s.totalCardsResourced,
               s.remainingHealthInWins, s.winsGoingFirst, s.winsGoingSecond
        FROM deckstats s
        LEFT JOIN deckstats c ON c.deckID = s.deckID AND c.version = s.version AND c.source = 0
        WHERE s.deckID = ? AND s.source = 1 AND c.deckID IS NULL
    ", [$deckID], "i");
    echo "deckstats — inserted new community rows: $affected\n";

    $affected = runQuery($conn, "DELETE FROM deckstats WHERE deckID = ? AND source = 1", [$deckID], "i");
    echo "deckstats — deleted (source=1): $affected rows\n\n";

    // ---- 2. carddeckstats ---------------------------------------------------
    // Merge into existing community rows
    $affected = runQuery($conn, "
        UPDATE carddeckstats AS comm
        JOIN carddeckstats AS owner
            ON owner.deckID = comm.deckID AND owner.cardID = comm.cardID AND owner.version = comm.version AND owner.source = 1
        SET comm.timesIncluded        = comm.timesIncluded        + owner.timesIncluded,
            comm.timesIncludedInWins  = comm.timesIncludedInWins  + owner.timesIncludedInWins,
            comm.timesPlayed          = comm.timesPlayed          + owner.timesPlayed,
            comm.timesPlayedInWins    = comm.timesPlayedInWins    + owner.timesPlayedInWins,
            comm.timesResourced       = comm.timesResourced       + owner.timesResourced,
            comm.timesResourcedInWins = comm.timesResourcedInWins + owner.timesResourcedInWins,
            comm.timesDiscarded       = comm.timesDiscarded       + owner.timesDiscarded,
            comm.timesDiscardedInWins = comm.timesDiscardedInWins + owner.timesDiscardedInWins,
            comm.timesDrawn           = comm.timesDrawn           + owner.timesDrawn,
            comm.timesDrawnInWins     = comm.timesDrawnInWins     + owner.timesDrawnInWins
        WHERE comm.deckID = ? AND comm.source = 0
    ", [$deckID], "i");
    echo "carddeckstats — merged into existing community rows: $affected\n";

    // Insert community rows for card+version combos that have no community entry yet
    $affected = runQuery($conn, "
        INSERT INTO carddeckstats
            (deckID, cardID, version, source,
             timesIncluded,   timesIncludedInWins,
             timesPlayed,     timesPlayedInWins,
             timesResourced,  timesResourcedInWins,
             timesDiscarded,  timesDiscardedInWins,
             timesDrawn,      timesDrawnInWins)
        SELECT s.deckID, s.cardID, s.version, 0,
               s.timesIncluded,   s.timesIncludedInWins,
               s.timesPlayed,     s.timesPlayedInWins,
               s.timesResourced,  s.timesResourcedInWins,
               s.timesDiscarded,  s.timesDiscardedInWins,
               s.timesDrawn,      s.timesDrawnInWins
        FROM carddeckstats s
        LEFT JOIN carddeckstats c
            ON c.deckID = s.deckID AND c.cardID = s.cardID AND c.version = s.version AND c.source = 0
        WHERE s.deckID = ? AND s.source = 1 AND c.deckID IS NULL
    ", [$deckID], "i");
    echo "carddeckstats — inserted new community rows: $affected\n";

    $affected = runQuery($conn, "DELETE FROM carddeckstats WHERE deckID = ? AND source = 1", [$deckID], "i");
    echo "carddeckstats — deleted (source=1): $affected rows\n\n";

    // ---- 3. opponentdeckstats -----------------------------------------------
    // Merge into existing community rows
    $affected = runQuery($conn, "
        UPDATE opponentdeckstats AS comm
        JOIN opponentdeckstats AS owner
            ON owner.deckID = comm.deckID AND owner.leaderID = comm.leaderID AND owner.version = comm.version AND owner.source = 1
        SET comm.winsVsGreen      = comm.winsVsGreen      + owner.winsVsGreen,
            comm.totalVsGreen     = comm.totalVsGreen     + owner.totalVsGreen,
            comm.winsVsBlue       = comm.winsVsBlue       + owner.winsVsBlue,
            comm.totalVsBlue      = comm.totalVsBlue      + owner.totalVsBlue,
            comm.winsVsRed        = comm.winsVsRed        + owner.winsVsRed,
            comm.totalVsRed       = comm.totalVsRed       + owner.totalVsRed,
            comm.winsVsYellow     = comm.winsVsYellow     + owner.winsVsYellow,
            comm.totalVsYellow    = comm.totalVsYellow    + owner.totalVsYellow,
            comm.winsVsColorless  = comm.winsVsColorless  + owner.winsVsColorless,
            comm.totalVsColorless = comm.totalVsColorless + owner.totalVsColorless
        WHERE comm.deckID = ? AND comm.source = 0
    ", [$deckID], "i");
    echo "opponentdeckstats — merged into existing community rows: $affected\n";

    // Insert community rows for leader+version combos that have no community entry yet
    $affected = runQuery($conn, "
        INSERT INTO opponentdeckstats
            (deckID, leaderID, version, source,
             winsVsGreen,     totalVsGreen,
             winsVsBlue,      totalVsBlue,
             winsVsRed,       totalVsRed,
             winsVsYellow,    totalVsYellow,
             winsVsColorless, totalVsColorless)
        SELECT s.deckID, s.leaderID, s.version, 0,
               s.winsVsGreen,     s.totalVsGreen,
               s.winsVsBlue,      s.totalVsBlue,
               s.winsVsRed,       s.totalVsRed,
               s.winsVsYellow,    s.totalVsYellow,
               s.winsVsColorless, s.totalVsColorless
        FROM opponentdeckstats s
        LEFT JOIN opponentdeckstats c
            ON c.deckID = s.deckID AND c.leaderID = s.leaderID AND c.version = s.version AND c.source = 0
        WHERE s.deckID = ? AND s.source = 1 AND c.deckID IS NULL
    ", [$deckID], "i");
    echo "opponentdeckstats — inserted new community rows: $affected\n";

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
