<?php
// DevTools/find_corrupted_sideboards.php
//
// Scans SWUDeck deck files and reports decks whose SIDEBOARD was corrupted by the Leader1/Leader2
// format break (an old-format deck opened in the editor -> misaligned parse -> autosave wrote a
// garbage/empty sideboard). Detection is content-based: it walks each file to the Sideboard zone and
// checks whether every sideboard entry is a real, playable card. A corrupted sideboard contains
// non-card tokens (e.g. "-", "SetNum", "0") or ids that aren't playable cards (leaders/bases/unknown).
//
// Usage (from repo root):
//   php DevTools/find_corrupted_sideboards.php              # report suspected-corrupted deck ids
//   php DevTools/find_corrupted_sideboards.php --verbose    # also list ok/empty/old-format decks
//   php DevTools/find_corrupted_sideboards.php --only 1,999005   # inspect specific deck ids
//   php DevTools/find_corrupted_sideboards.php --dir SWUDeck/Games

require_once __DIR__ . '/../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$verbose = in_array('--verbose', $argv, true);
$gamesDir = 'SWUDeck/Games';
$only = null;
for ($i = 1; $i < count($argv); $i++) {
    if ($argv[$i] === '--dir' && isset($argv[$i+1])) { $gamesDir = rtrim($argv[++$i], '/'); }
    else if (strpos($argv[$i], '--dir=') === 0) { $gamesDir = rtrim(substr($argv[$i], 6), '/'); }
    else if ($argv[$i] === '--only' && isset($argv[$i+1])) { $only = array_map('trim', explode(',', $argv[++$i])); }
    else if (strpos($argv[$i], '--only=') === 0) { $only = array_map('trim', explode(',', substr($argv[$i], 7))); }
}

// Read one zone's player-1 entries; advance $idx past BOTH players. Returns array of trimmed entries,
// or null on malformed structure.
function readZoneP1(array $lines, int &$idx): ?array {
    $n = count($lines);
    if ($idx >= $n) return null;
    $c1 = intval($lines[$idx++]); if ($c1 < 0 || $idx + $c1 > $n) return null;
    $p1 = [];
    for ($i = 0; $i < $c1; $i++) $p1[] = trim($lines[$idx++]);
    if ($idx >= $n) return null;
    $c2 = intval($lines[$idx++]); if ($c2 < 0 || $idx + $c2 > $n) return null;
    $idx += $c2;
    return $p1;
}

// A valid sideboard card: known to the dictionary and playable (not a Leader/Base, not a token/dash).
function isValidSideboardCard(string $id): bool {
    if ($id === '' || $id === '-' || !ctype_digit($id)) return false; // "-", "SetNum", empty, etc.
    $type = CardType($id);
    if ($type === null || $type === '' ) return false;                 // unknown id
    if ($type === 'Leader' || $type === 'Base') return false;          // wrong zone => misaligned
    return true;
}

// Extract the Sideboard(p1) entries for a deck file, handling old (1 leader-pool) and new (3) formats.
// Returns ['format'=>'old'|'new'|'?', 'sideboard'=>[...] or null].
function extractSideboard(string $raw): array {
    $lines = explode("\n", $raw);
    if (count($lines) < 2) return ['format' => '?', 'sideboard' => null];
    $idx = 2; // skip currentPlayer, updateNumber
    // Leader, Base, MainDeck, CardPane
    foreach (['Leader','Base','MainDeck','CardPane'] as $_z) { if (readZoneP1($lines, $idx) === null) return ['format'=>'?','sideboard'=>null]; }
    if (readZoneP1($lines, $idx) === null) return ['format'=>'?','sideboard'=>null]; // Leaders
    // Detect format by first card of the block after Leaders.
    $n = count($lines);
    $peekCount = ($idx < $n) ? intval($lines[$idx]) : 0;
    $peekFirst = ($peekCount > 0 && ($idx+1) < $n) ? trim($lines[$idx+1]) : '';
    $peekType = ($peekFirst !== '') ? CardType($peekFirst) : '';
    $format = ($peekType === 'Leader') ? 'new' : (($peekType === 'Base') ? 'old' : '?');
    if ($format === 'new') { // consume Leader1, Leader2
        if (readZoneP1($lines, $idx) === null) return ['format'=>'new','sideboard'=>null];
        if (readZoneP1($lines, $idx) === null) return ['format'=>'new','sideboard'=>null];
    } else if ($format === '?') {
        return ['format'=>'?','sideboard'=>null];
    }
    if (readZoneP1($lines, $idx) === null) return ['format'=>$format,'sideboard'=>null]; // Bases
    if (readZoneP1($lines, $idx) === null) return ['format'=>$format,'sideboard'=>null]; // Cards
    $sideboard = readZoneP1($lines, $idx);                                               // Sideboard
    return ['format'=>$format, 'sideboard'=>$sideboard];
}

if (!is_dir($gamesDir)) { fwrite(STDERR, "Games dir not found: $gamesDir\n"); exit(2); }

$corrupted = []; $counts = ['scanned'=>0,'corrupted'=>0,'ok'=>0,'empty'=>0,'old_intact'=>0,'unparseable'=>0];
$rows = [];

foreach (glob($gamesDir . '/*', GLOB_ONLYDIR) as $deckDir) {
    $file = $deckDir . '/Gamestate.txt';
    if (!is_file($file)) continue;
    $deckId = basename($deckDir);
    if ($only !== null && !in_array($deckId, $only, true)) continue;
    $counts['scanned']++;

    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') { $counts['unparseable']++; $rows[] = [$deckId,'?','unparseable','']; continue; }

    $r = extractSideboard($raw);
    $sb = $r['sideboard'];
    if ($r['format'] === '?' || $sb === null) { $counts['unparseable']++; $rows[] = [$deckId, $r['format'], 'unparseable','']; continue; }

    if (count($sb) === 0) {
        $counts['empty']++; $rows[] = [$deckId, $r['format'], 'empty-sideboard', ''];
        continue;
    }
    $bad = array_values(array_filter($sb, fn($c) => !isValidSideboardCard($c)));
    if (count($bad) > 0) {
        // Corrupted: sideboard contains non-card / wrong-zone tokens.
        $corrupted[] = $deckId; $counts['corrupted']++;
        $rows[] = [$deckId, $r['format'], 'CORRUPTED', 'bad=[' . implode(',', array_slice($bad,0,5)) . '] of ' . count($sb)];
    } else if ($r['format'] === 'old') {
        $counts['old_intact']++; $rows[] = [$deckId,'old','old-intact-sideboard(' . count($sb) . ')',''];
    } else {
        $counts['ok']++; $rows[] = [$deckId,'new','ok-sideboard(' . count($sb) . ')',''];
    }
}

// Report
if ($verbose || $only !== null) {
    foreach ($rows as $row) printf("  deck %-10s [%-3s] %-28s %s\n", $row[0], $row[1], $row[2], $row[3]);
    echo "---\n";
}
echo "Suspected-corrupted sideboards (" . count($corrupted) . "):\n";
echo count($corrupted) ? "  " . implode("\n  ", $corrupted) . "\n" : "  (none)\n";
echo "---\n";
foreach ($counts as $k => $v) echo "  $k: $v\n";
echo "\nNote: 'empty-sideboard' decks are NOT flagged (indistinguishable from a legit no-sideboard deck).\n";
echo "If corruption emptied a sideboard to 0, it can't be detected from the file alone — cross-check\n";
echo "against assetversions for decks you expect to have had a sideboard.\n";
