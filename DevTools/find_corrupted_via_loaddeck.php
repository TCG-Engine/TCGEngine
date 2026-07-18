<?php
// DevTools/find_corrupted_via_loaddeck.php
//
// Scans SWUDeck decks via the LoadDeck.php API (the REAL app parser) and flags decks whose sideboard
// comes back with a null/invalid card id (the fingerprint of a sideboard the current parser can't
// read). Read-only: LoadDeck only parses, it never writes the gamestate.
//
// IMPORTANT ordering: LoadDeck uses the current parser, so BEFORE migration it also flags INTACT
// old-format decks (their data is fine, just misread). Run this AFTER migrate_swudeck_leader2.php so
// a null sideboard here means the deck is GENUINELY corrupted (data lost). Cross-check flagged ids
// against assetversions to recover.
//
// Usage (from repo root):
//   php DevTools/find_corrupted_via_loaddeck.php                       # scan all decks in ownership, localhost
//   php DevTools/find_corrupted_via_loaddeck.php --base https://swustats.net/TCGEngine
//   php DevTools/find_corrupted_via_loaddeck.php --ids 100938,100431   # only these deckIds
//   php DevTools/find_corrupted_via_loaddeck.php --verbose

require_once __DIR__ . '/../Database/ConnectionManager.php';

$base = 'http://localhost:3100/TCGEngine';
$verbose = false;
$ids = null;
for ($i = 1; $i < count($argv); $i++) {
    if ($argv[$i] === '--base' && isset($argv[$i+1])) $base = rtrim($argv[++$i], '/');
    else if (strpos($argv[$i], '--base=') === 0) $base = rtrim(substr($argv[$i], 7), '/');
    else if ($argv[$i] === '--ids' && isset($argv[$i+1])) $ids = array_map('trim', explode(',', $argv[++$i]));
    else if (strpos($argv[$i], '--ids=') === 0) $ids = array_map('trim', explode(',', substr($argv[$i], 6)));
    else if ($argv[$i] === '--verbose') $verbose = true;
}

// Deck id list: explicit --ids, else every SWUDeck deck (ownership assetType=1).
if ($ids === null) {
    $conn = GetLocalMySQLConnection();
    $ids = [];
    $res = $conn->query("SELECT assetIdentifier FROM ownership WHERE assetType = 1 ORDER BY assetIdentifier");
    while ($row = $res->fetch_assoc()) $ids[] = $row['assetIdentifier'];
    $conn->close();
}

function loadDeckSideboard(string $base, string $id) {
    $url = $base . '/APIs/LoadDeck.php?deckID=' . rawurlencode($id) . '&setId=true';
    $ctx = stream_context_create(['http' => ['timeout' => 15]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return ['err' => 'request failed'];
    $j = json_decode($raw, true);
    if (!is_array($j)) return ['err' => 'bad json'];
    if (isset($j['error'])) return ['err' => $j['error']];
    return ['sideboard' => $j['sideboard'] ?? []];
}

$corrupted = []; $counts = ['scanned'=>0,'corrupted'=>0,'ok'=>0,'empty'=>0,'errors'=>0]; $rows = [];
foreach ($ids as $id) {
    $counts['scanned']++;
    $r = loadDeckSideboard($base, $id);
    if (isset($r['err'])) { $counts['errors']++; if ($verbose) $rows[] = [$id, 'ERROR', $r['err']]; continue; }
    $sb = $r['sideboard'];
    if (!is_array($sb) || count($sb) === 0) { $counts['empty']++; if ($verbose) $rows[] = [$id, 'empty', '']; continue; }
    // Flag if any sideboard entry has a null/empty id (a card the parser couldn't resolve).
    $bad = 0; foreach ($sb as $c) { $cid = $c['id'] ?? null; if ($cid === null || $cid === '' ) $bad++; }
    if ($bad > 0) { $corrupted[] = $id; $counts['corrupted']++; $rows[] = [$id, 'CORRUPTED', "$bad null-id entr(y/ies) of " . count($sb)]; }
    else { $counts['ok']++; if ($verbose) $rows[] = [$id, 'ok', count($sb) . ' cards']; }
}

if ($verbose) { foreach ($rows as $row) printf("  deck %-10s %-11s %s\n", $row[0], $row[1], $row[2]); echo "---\n"; }
echo "Corrupted (null-sideboard) deckIds (" . count($corrupted) . "):\n";
echo count($corrupted) ? "  " . implode("\n  ", $corrupted) . "\n" : "  (none)\n";
echo "---\n";
foreach ($counts as $k => $v) echo "  $k: $v\n";
echo "\nRun AFTER migrate_swudeck_leader2.php, or intact old-format decks will also be flagged.\n";
