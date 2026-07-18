<?php
// DevTools/find_corrupted_via_loaddeck.php
//
// Scans SWUDeck decks through the LoadDeck.php API (the REAL app parser) and reports decks whose
// sideboard comes back with a null card id (the fingerprint of a sideboard the current parser can't
// read -> the corruption from the Leader1/Leader2 format break). Read-only: LoadDeck only parses.
//
// No DB driver needed (prod LAMPP CLI has no mysqli): deck ids come from STDIN, a file, or --ids.
// Only HTTP + JSON happen in PHP, which the CLI always supports.
//
// IMPORTANT ordering: LoadDeck uses the current parser, so BEFORE migration it ALSO flags intact
// old-format decks (data fine, just misread). Run this AFTER migrate_swudeck_leader2.php so a flagged
// deck is GENUINELY corrupted; then recover it from assetversions.
//
// Get the deck ids from the DB with your own client and pipe them in (honors "all gameids from db"):
//   mysql -u <user> -p<pass> <db> -N -e \
//     "SELECT assetIdentifier FROM ownership WHERE assetType = 1" \
//     | php DevTools/find_corrupted_via_loaddeck.php --base https://swustats.net/TCGEngine
//
// Other id sources:
//   php DevTools/find_corrupted_via_loaddeck.php --ids-file ids.txt --base https://swustats.net/TCGEngine
//   php DevTools/find_corrupted_via_loaddeck.php --ids 100938,100431 --base https://swustats.net/TCGEngine
//   php DevTools/find_corrupted_via_loaddeck.php --base https://swustats.net/TCGEngine   # (no ids piped -> scan SWUDeck/Games/* folder names)
//
// Flags: --base <url> (required for prod), --verbose, --sleep <ms> (politeness between calls, default 0),
//        --ids <csv>, --ids-file <path>, --games-dir <dir> (default SWUDeck/Games)

$base = 'https://swustats.net/TCGEngine';
$verbose = false; $sleepMs = 0; $ids = null; $idsFile = null; $gamesDir = 'SWUDeck/Games';
for ($i = 1; $i < count($argv); $i++) {
    $a = $argv[$i];
    if ($a === '--base' && isset($argv[$i+1])) $base = rtrim($argv[++$i], '/');
    else if (strpos($a, '--base=') === 0) $base = rtrim(substr($a, 7), '/');
    else if ($a === '--verbose') $verbose = true;
    else if ($a === '--sleep' && isset($argv[$i+1])) $sleepMs = intval($argv[++$i]);
    else if (strpos($a, '--sleep=') === 0) $sleepMs = intval(substr($a, 8));
    else if ($a === '--ids' && isset($argv[$i+1])) $ids = preg_split('/[,\s]+/', trim($argv[++$i]), -1, PREG_SPLIT_NO_EMPTY);
    else if (strpos($a, '--ids=') === 0) $ids = preg_split('/[,\s]+/', trim(substr($a, 6)), -1, PREG_SPLIT_NO_EMPTY);
    else if ($a === '--ids-file' && isset($argv[$i+1])) $idsFile = $argv[++$i];
    else if (strpos($a, '--ids-file=') === 0) $idsFile = substr($a, 11);
    else if ($a === '--games-dir' && isset($argv[$i+1])) $gamesDir = rtrim($argv[++$i], '/');
    else if (strpos($a, '--games-dir=') === 0) $gamesDir = rtrim(substr($a, 12), '/');
}

// Resolve deck id list. Priority: --ids, --ids-file, piped STDIN, else Games/ folders.
function normalizeIds($raw): array {
    $out = [];
    foreach (preg_split('/[,\s]+/', (string)$raw, -1, PREG_SPLIT_NO_EMPTY) as $t) {
        $t = trim($t);
        if ($t !== '' && ctype_digit($t)) $out[] = $t;   // deck ids are numeric
    }
    return $out;
}
if ($ids === null && $idsFile !== null) {
    if (!is_file($idsFile)) { fwrite(STDERR, "ids file not found: $idsFile\n"); exit(2); }
    $ids = normalizeIds(file_get_contents($idsFile));
}
if ($ids === null && !stream_isatty(STDIN)) {           // ids piped in
    $stdin = stream_get_contents(STDIN);
    if (trim($stdin) !== '') $ids = normalizeIds($stdin);
}
if ($ids === null) {                                     // fallback: folder names
    $ids = [];
    if (is_dir($gamesDir)) foreach (glob($gamesDir . '/*', GLOB_ONLYDIR) as $d) { $b = basename($d); if (ctype_digit($b)) $ids[] = $b; }
}
$ids = array_values(array_unique($ids));
if (count($ids) === 0) { fwrite(STDERR, "No deck ids to scan (pipe them in, use --ids/--ids-file, or ensure --games-dir exists).\n"); exit(2); }

function loadDeckSideboard(string $base, string $id) {
    $url = $base . '/APIs/LoadDeck.php?deckID=' . rawurlencode($id) . '&setId=true';
    $ctx = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return ['err' => 'request failed'];
    $j = json_decode($raw, true);
    if (!is_array($j)) return ['err' => 'bad json'];
    if (isset($j['error'])) return ['err' => $j['error']];
    return ['sideboard' => $j['sideboard'] ?? []];
}

fwrite(STDERR, "Scanning " . count($ids) . " decks via $base ...\n");
$corrupted = []; $counts = ['scanned'=>0,'corrupted'=>0,'ok'=>0,'empty'=>0,'errors'=>0]; $rows = [];
foreach ($ids as $id) {
    $counts['scanned']++;
    $r = loadDeckSideboard($base, $id);
    if (isset($r['err'])) { $counts['errors']++; if ($verbose) $rows[] = [$id, 'ERROR', $r['err']]; }
    else {
        $sb = $r['sideboard'];
        if (!is_array($sb) || count($sb) === 0) { $counts['empty']++; if ($verbose) $rows[] = [$id, 'empty', '']; }
        else {
            $nullIds = 0; foreach ($sb as $c) { $cid = $c['id'] ?? null; if ($cid === null || $cid === '') $nullIds++; }
            if ($nullIds > 0) { $corrupted[] = $id; $counts['corrupted']++; $rows[] = [$id, 'CORRUPTED', "$nullIds null-id of " . count($sb)]; }
            else { $counts['ok']++; if ($verbose) $rows[] = [$id, 'ok', count($sb) . ' cards']; }
        }
    }
    if ($sleepMs > 0) usleep($sleepMs * 1000);
    if ($counts['scanned'] % 250 === 0) fwrite(STDERR, "  ..." . $counts['scanned'] . " scanned (" . $counts['corrupted'] . " flagged)\n");
}

if ($verbose) { foreach ($rows as $row) printf("  deck %-10s %-11s %s\n", $row[0], $row[1], $row[2]); echo "---\n"; }
echo "Corrupted (null-sideboard) deckIds (" . count($corrupted) . "):\n";
echo count($corrupted) ? implode("\n", $corrupted) . "\n" : "(none)\n";
echo "---\n";
foreach ($counts as $k => $v) fwrite(STDERR, "  $k: $v\n");
fwrite(STDERR, "\nRun AFTER migrate_swudeck_leader2.php, else intact old-format decks are also flagged.\n");
