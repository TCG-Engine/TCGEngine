<?php
// 10-preview-orphan-cleanup.php — delete stat rows that reference a mock card deleted at release.
//
// RUN:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 \
//     php AppCore/SWU/migrations/10-preview-orphan-cleanup.php            # dry run
//     php AppCore/SWU/migrations/10-preview-orphan-cleanup.php --apply    # write
//
// Runs AFTER the sunset checklist's "delete superseded mocks" step and a regeneration. Most mock ids
// ARE the official id — HMW_004 mock becomes HMW_004 official — so those rows stay valid and simply
// become rows about a real card. Orphans are the rest: mocks renumbered before release, or cards
// that never shipped. Their rows dangle and render as blank titles with broken art.
//
// DELETES only where BOTH hold:
//   • the identifier is no longer in the generated dictionary, AND
//   • the row's format is a PREVIEW format
// The second condition is the safety rail — released-format data can never be touched, whatever the
// first condition matches.
//
// DRY RUN BY DEFAULT. Pass --apply to write.
//
// Design: docs/superpowers/specs/2026-08-06-swu-preview-format-stats-design.md §6
error_reporting(E_ALL & ~E_DEPRECATED);
$root = dirname(__DIR__, 3);
chdir($root);
require_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once './AppCore/SWU/Formats.php';
require_once './AppCore/SWU/CardIdentity.php';   // SWUCardIdentityBaseColours()
require_once './Database/ConnectionManager.php';

$apply = in_array('--apply', array_slice($argv, 1), true);

$previewFormats = array_values(array_filter(SWUStatsFormats(false), 'SWUFormatIsPreview'));
if (empty($previewFormats)) {
    fwrite(STDERR, "No preview formats registered — nothing to do.\n");
    exit(0);
}

// table => identifier columns. The same columns the SET_NNN migration enumerated.
$targets = [
    'carddeckstats'          => ['cardID'],
    'cardmetastats'          => ['cardID'],
    'deckmetastats'          => ['leaderID', 'baseID'],
    'deckmetamatchupstats'   => ['leaderID', 'baseID', 'opponentLeaderID', 'opponentBaseID'],
    'opponentdeckstats'      => ['leaderID'],
    'opponentnamedbasestats' => ['leaderID', 'baseID'],
    'completedgame'          => ['WinningHero', 'LosingHero'],
];

$conn = GetLocalMySQLConnection();
$fmtIn = implode(',', array_map(fn($f) => "'" . $conn->real_escape_string($f) . "'", $previewFormats));
echo ($apply ? "APPLY" : "DRY RUN") . " — preview formats: " . implode(', ', $previewFormats) . "\n";

$totalRows = 0;
foreach ($targets as $table => $cols) {
    // completedgame capitalises its format column; every other table uses lowercase.
    $formatCol = ($table === 'completedgame') ? 'Format' : 'format';
    foreach ($cols as $col) {
        $res = $conn->query("SELECT DISTINCT `$col` v FROM `$table` WHERE `$formatCol` IN ($fmtIn)");
        if (!$res) { echo "  SKIP $table.$col (" . $conn->error . ")\n"; continue; }
        $orphans = [];
        while ($row = $res->fetch_assoc()) {
            $v = (string)$row['v'];
            if ($v === '') continue;
            // A base may legitimately be a COLOUR name rather than a card id — never an orphan.
            if (in_array(strtolower($v), SWUCardIdentityBaseColours(), true)) continue;
            if (CardTitle($v) === null) $orphans[] = $v;
        }
        if (empty($orphans)) { echo "  $table.$col: 0 orphans\n"; continue; }
        $list = implode(',', array_map(fn($o) => "'" . $conn->real_escape_string($o) . "'", $orphans));
        $cnt = intval($conn->query(
            "SELECT COUNT(*) c FROM `$table` WHERE `$formatCol` IN ($fmtIn) AND `$col` IN ($list)"
        )->fetch_assoc()['c']);
        $totalRows += $cnt;
        echo "  $table.$col: " . count($orphans) . " orphan id(s), $cnt row(s) ["
           . implode(', ', array_slice($orphans, 0, 6)) . (count($orphans) > 6 ? ', …' : '') . "]\n";
        if ($apply) {
            $conn->query("DELETE FROM `$table` WHERE `$formatCol` IN ($fmtIn) AND `$col` IN ($list)");
            echo "    deleted " . $conn->affected_rows . "\n";
        }
    }
}
echo ($apply ? "Done. " : "DRY RUN — nothing written. ") . "$totalRows row(s) affected.\n";
if (!$apply) echo "Re-run with --apply to delete.\n";
