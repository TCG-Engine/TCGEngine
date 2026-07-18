<?php
// DevTools/migrate_swudeck_leader2.php
//
// Backfills the Leader1/Leader2 browse-pool zones into OLD-format SWUDeck deck files so the current
// (post-Twin-Suns) parser reads them correctly. Old files (saved before Leader1/Leader2 existed) have
// ONE leader pool; the current parser expects THREE, so it reads every zone after the pools at the
// wrong offset and DESTROYS the sideboard on the next save. This inserts two copies of the existing
// `Leaders` pool block (matching exactly what a real new-format save writes — see Initialize.php,
// where Leader1/Leader2 get the same full leader catalog as Leaders) right after `Leaders`.
//
// SAFE BY CONSTRUCTION: it only INSERTS duplicated leader-pool lines; every other byte (MainDeck,
// Bases, Cards, Sideboard, ...) is preserved and simply shifts back into correct alignment.
//
// Usage (run from repo root):
//   php DevTools/migrate_swudeck_leader2.php            # dry run: report only
//   php DevTools/migrate_swudeck_leader2.php --execute   # write changes (.bak backup per file)
//   php DevTools/migrate_swudeck_leader2.php --execute --dir SWUDeck/Games   # explicit games dir
//
// Detection: after consuming the `Leaders` pool, it inspects the first card of the NEXT block. If
// that card is a Leader, the file already has Leader1 (new format) -> SKIP. If it's a Base, the next
// block is `Bases` (old format) -> MIGRATE. Files it can't confidently classify are SKIPPED and logged.

$isCli = (php_sapi_name() === 'cli');
$opts = [];
foreach (array_slice($argv, 1) as $a) {
    if ($a === '--execute') $opts['execute'] = true;
    else if (strpos($a, '--dir=') === 0) $opts['dir'] = substr($a, 6);
    else if ($a === '--dir') { /* value follows */ $opts['_dirnext'] = true; }
    else if (!empty($opts['_dirnext'])) { $opts['dir'] = $a; unset($opts['_dirnext']); }
}
$execute = !empty($opts['execute']);
$gamesDir = isset($opts['dir']) ? rtrim($opts['dir'], '/') : 'SWUDeck/Games';

require_once __DIR__ . '/../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

if (!is_dir($gamesDir)) { fwrite(STDERR, "Games dir not found: $gamesDir\n"); exit(2); }

// Consume one zone (both players) starting at $idx; advance $idx past it. Returns false on malformed.
function consumeZone(array $lines, int &$idx): bool {
    $n = count($lines);
    for ($p = 0; $p < 2; $p++) {
        if ($idx >= $n) return false;
        $count = intval($lines[$idx++]);
        if ($count < 0) return false;
        $idx += $count;
        if ($idx > $n) return false;
    }
    return true;
}

$stats = ['scanned' => 0, 'migrated' => 0, 'already_new' => 0, 'skipped' => 0, 'errors' => 0];
$log = [];

foreach (glob($gamesDir . '/*', GLOB_ONLYDIR) as $deckDir) {
    $file = $deckDir . '/Gamestate.txt';
    if (!is_file($file)) continue;
    $deckId = basename($deckDir);
    $stats['scanned']++;

    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') { $log[] = "deck $deckId: EMPTY/unreadable -> skip"; $stats['skipped']++; continue; }

    // Split on \n; each line may keep a trailing \r (\r\n files). Inserted lines are copied verbatim,
    // so line endings stay consistent. Rejoin with \n.
    $lines = explode("\n", $raw);
    $idx = 0;
    // header: currentPlayer, updateNumber
    if (count($lines) < 2) { $log[] = "deck $deckId: too short -> skip"; $stats['skipped']++; continue; }
    $idx = 2;
    // Consume the four fixed zones, then Leaders. Zone order: Leader, Base, MainDeck, CardPane, Leaders.
    $ok = true;
    foreach (['Leader','Base','MainDeck','CardPane'] as $_z) { if (!consumeZone($lines, $idx)) { $ok = false; break; } }
    if (!$ok) { $log[] = "deck $deckId: malformed header zones -> skip"; $stats['skipped']++; $stats['errors']++; continue; }

    $leadersStart = $idx;
    if (!consumeZone($lines, $idx)) { $log[] = "deck $deckId: malformed Leaders zone -> skip"; $stats['skipped']++; $stats['errors']++; continue; }
    $leadersEnd = $idx; // insertion point (start of the block after Leaders)

    // Detect old vs new by the first card of the NEXT block.
    $nextCount = ($leadersEnd < count($lines)) ? intval($lines[$leadersEnd]) : 0;
    $firstEntry = ($nextCount > 0 && ($leadersEnd + 1) < count($lines)) ? trim($lines[$leadersEnd + 1]) : '';
    $nextType = ($firstEntry !== '') ? CardType($firstEntry) : '';

    if ($nextType === 'Leader') { $log[] = "deck $deckId: already new format -> skip"; $stats['already_new']++; continue; }
    if ($nextType !== 'Base') { $log[] = "deck $deckId: cannot classify block after Leaders (first='$firstEntry', type='$nextType') -> skip"; $stats['skipped']++; continue; }

    // OLD format confirmed. Insert two copies of the Leaders block (Leader1, Leader2) at $leadersEnd.
    $leadersBlock = array_slice($lines, $leadersStart, $leadersEnd - $leadersStart);
    $migrated = $lines;
    array_splice($migrated, $leadersEnd, 0, array_merge($leadersBlock, $leadersBlock));
    $out = implode("\n", $migrated);

    if ($execute) {
        // Backup once (never overwrite an existing .bak), then write.
        if (!is_file($file . '.bak')) { file_put_contents($file . '.bak', $raw); }
        file_put_contents($file, $out);
    }
    $log[] = "deck $deckId: OLD -> migrated (" . count($leadersBlock) . " lines x2 inserted)" . ($execute ? "" : " [dry-run]");
    $stats['migrated']++;
}

echo ($execute ? "EXECUTE" : "DRY-RUN") . " over $gamesDir\n";
foreach ($log as $l) echo "  $l\n";
echo "---\n";
foreach ($stats as $k => $v) echo "  $k: $v\n";
echo ($execute ? "Done. Backups written as <file>.bak.\n" : "Dry run only. Re-run with --execute to write.\n");
