<?php
// verify-event-migration.php — completeness check for the OnPlayEvent → $whenPlayedAbilities
// migration. STATIC scan (no engine boot): confirms every event CardID that was in the
// OnPlayEvent switch now has a `$whenPlayedAbilities["CARDID:0"] = ...` assignment somewhere in
// SWUSim/Custom/. Runtime correctness is covered by the full regression suite; this only proves
// nothing was dropped or mis-keyed.
//
// Usage:
//   php verify-event-migration.php          # check all 433
//   php verify-event-migration.php SOR       # check only one set prefix
//
// Exit 0 = every checked CardID has a registration; exit 1 = some missing (listed on stderr).

$devtools = __DIR__;
$custom   = dirname($devtools) . '/Custom';

$inv = array_filter(array_map('trim', file($devtools . '/event-migration-inventory.txt')));

// Collect every registered $whenPlayedAbilities["CARDID:0"] assignment key across Custom/.
$registered = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($custom, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
    if ($file->getExtension() !== 'php') continue;
    $src = file_get_contents($file->getPathname());
    if (preg_match_all('/\$whenPlayedAbilities\s*\[\s*"([A-Z0-9]+_[0-9]+):0"\s*\]\s*=/', $src, $m)) {
        foreach ($m[1] as $cardID) $registered[$cardID] = true;
    }
}

$prefix  = $argv[1] ?? '';
$missing = [];
foreach ($inv as $cardID) {
    if ($prefix !== '' && strpos($cardID, $prefix . '_') !== 0) continue;
    if (!isset($registered[$cardID])) $missing[] = $cardID;
}

if ($missing) {
    fwrite(STDERR, "MISSING " . count($missing) . " registration(s):\n  " . implode("\n  ", $missing) . "\n");
    exit(1);
}
echo "OK: all " . ($prefix !== '' ? $prefix : 'event') . " CardIDs have a whenPlayed registration ("
    . count(array_filter($inv, fn($c) => $prefix === '' || strpos($c, $prefix . '_') === 0)) . " checked)\n";
