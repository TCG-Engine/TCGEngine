<?php
// A tournament package is a recording of every melee.gg answer one tournament import needed, so
// swustats.net — which melee 403s (2026-10-22) — can import a tournament without the network.
// Built by DevTools/melee-package.php (the swustats-melee-package skill); uploaded by a moderator to
// APIs/ImportMeleeTournamentPackage.php from Stats/MeleeTournaments.php. See Stats/MeleeFetch.php.
//
// File: melee-<meleeId>.json.gz =
//   { format, melee_tournament_id, source_url, created_at, parser_version,
//     summary: { tournament_name, decks, matchups },
//     responses: [ { method, url, body, status, error, payload }, ... ] }

require_once __DIR__ . '/MeleeFetch.php';

define('SWU_MELEE_PACKAGE_FORMAT', 'swustats-melee-package-v1');
define('SWU_MELEE_PACKAGE_MAX_FILES', 5);
// A 1485-player event is ~1500 decklist answers; compressed that is a few MB. Both caps sit well
// under the box's post_max_size (66M) for five files.
define('SWU_MELEE_PACKAGE_MAX_BYTES', 12 * 1024 * 1024);
define('SWU_MELEE_PACKAGE_MAX_JSON_BYTES', 96 * 1024 * 1024);

function SWUMeleeIdFromLink(string $link): ?string {
    return preg_match('~melee\.gg/(?:Tournament/View/|tournament/view/|tournament/)([0-9]+)~i', $link, $m) ? $m[1] : null;
}

// The local tournament for a melee id: ['tournamentID' => int, 'deckCount' => int], or null.
function SWUMeleeFindTournament($conn, string $meleeId): ?array {
    $stmt = $conn->prepare(
        'SELECT mt.tournamentID, (SELECT COUNT(*) FROM meleetournamentdeck mtd WHERE mtd.tournamentId = mt.tournamentID) AS deckCount
         FROM meleetournament mt WHERE mt.tournamentLink = ? LIMIT 1'
    );
    $stmt->bind_param('i', $meleeId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? ['tournamentID' => (int)$row['tournamentID'], 'deckCount' => (int)$row['deckCount']] : null;
}

function SWUMeleeTournamentSummary($conn, int $tournamentId): array {
    $stmt = $conn->prepare(
        'SELECT mt.tournamentName,
                (SELECT COUNT(*) FROM meleetournamentdeck d WHERE d.tournamentID = mt.tournamentID) AS decks,
                (SELECT COUNT(*) FROM meleetournamentmatchup m JOIN meleetournamentdeck d ON d.deckID = m.player
                  WHERE d.tournamentID = mt.tournamentID) AS matchups
         FROM meleetournament mt WHERE mt.tournamentID = ?'
    );
    $stmt->bind_param('i', $tournamentId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return [
        'tournament_name' => $row ? (string)$row['tournamentName'] : '',
        'decks' => $row ? (int)$row['decks'] : 0,
        'matchups' => $row ? (int)$row['matchups'] : 0,
    ];
}

// Raw .json.gz bytes -> the package array, or null with $error set.
function SWUMeleePackageDecode(string $raw, ?string &$error = null): ?array {
    $error = null;
    if (strlen($raw) > SWU_MELEE_PACKAGE_MAX_BYTES) { $error = 'The file is larger than the package limit.'; return null; }
    $json = @gzdecode($raw, SWU_MELEE_PACKAGE_MAX_JSON_BYTES);
    if ($json === false) { $error = 'Not a tournament package (it is not gzip, or it unpacks too large).'; return null; }
    $pkg = json_decode($json, true);
    if (!is_array($pkg) || ($pkg['format'] ?? null) !== SWU_MELEE_PACKAGE_FORMAT) {
        $error = 'Not a tournament package (unknown format).';
        return null;
    }
    if (!isset($pkg['melee_tournament_id']) || !ctype_digit((string)$pkg['melee_tournament_id'])) {
        $error = 'The package has no melee tournament id.';
        return null;
    }
    if (!isset($pkg['responses']) || !is_array($pkg['responses']) || empty($pkg['responses'])) {
        $error = 'The package has no recorded melee answers.';
        return null;
    }
    foreach ($pkg['responses'] as $r) {
        if (!is_array($r) || !isset($r['method'], $r['url']) || !is_string($r['url'])
            || strpos($r['url'], 'https://melee.gg/') !== 0) {
            $error = 'The package holds an answer that is not from melee.gg.';
            return null;
        }
    }
    $pkg['melee_tournament_id'] = (string)$pkg['melee_tournament_id'];
    return $pkg;
}

// Imports a decoded package by replaying it — the network is never used. Requires the importer
// (Stats/MeleeTournamentParserAPI.php) to be loaded. Returns
//   ['status' => 'imported' | 'already_imported' | 'failed', 'tournament_id' => ?int,
//    'message' => string, 'summary' => [...], 'expected' => [...]]
// 'imported' with summary != expected still succeeds, but carries a 'warning'.
function SWUMeleePackageImport($conn, array $pkg): array {
    $meleeId = (string)$pkg['melee_tournament_id'];
    $expected = is_array($pkg['summary'] ?? null) ? $pkg['summary'] : [];
    $existing = SWUMeleeFindTournament($conn, $meleeId);
    if ($existing && $existing['deckCount'] > 0) {
        return ['status' => 'already_imported', 'tournament_id' => $existing['tournamentID'],
            'message' => 'Already imported.', 'summary' => SWUMeleeTournamentSummary($conn, $existing['tournamentID']), 'expected' => $expected];
    }
    if ($existing) {
        // The empty shell of an import that died partway — clear it, or the parser's own
        // "already loaded" roundId check would short-circuit the replay.
        $del = $conn->prepare('DELETE FROM meleetournament WHERE tournamentID = ?');
        $del->bind_param('i', $existing['tournamentID']);
        $del->execute();
        $del->close();
    }

    $failureReason = null;
    $diag = null;
    SWUMeleeFetchReplay($pkg['responses']);
    ob_start(); // the parser echoes HTML progress notes
    try {
        $tournamentId = importMeleeTournamentById($meleeId, null, $failureReason, $diag);
    } catch (Throwable $e) {
        $tournamentId = false;
        $failureReason = $e->getMessage();
    } finally {
        ob_end_clean();
        SWUMeleeFetchLive();
    }
    if (!$tournamentId) {
        return ['status' => 'failed', 'tournament_id' => null,
            'message' => 'Import failed: ' . ($failureReason ?: 'unknown reason'), 'summary' => null, 'expected' => $expected];
    }
    $summary = SWUMeleeTournamentSummary($conn, (int)$tournamentId);
    $result = ['status' => 'imported', 'tournament_id' => (int)$tournamentId,
        'message' => 'Imported.', 'summary' => $summary, 'expected' => $expected];
    if (isset($expected['decks'], $expected['matchups'])
        && ((int)$expected['decks'] !== $summary['decks'] || (int)$expected['matchups'] !== $summary['matchups'])) {
        $result['warning'] = "Imported {$summary['decks']} decks / {$summary['matchups']} matchups; the package recorded "
            . "{$expected['decks']} / {$expected['matchups']}.";
    }
    return $result;
}
