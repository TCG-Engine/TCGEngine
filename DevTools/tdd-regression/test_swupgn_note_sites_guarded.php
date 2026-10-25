<?php
// SWU-PGN notes cost an unrecorded game one boolean check: every engine call site that writes a note is guarded by
// SwuPgnNoteOn() — so its arguments (a resolved target, a card's cost, a base scan) are only built for a recorded game.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_note_sites_guarded.php
chdir(__DIR__ . '/../..');
$files = array_merge(glob('SWUSim/Custom/*.php'), glob('SWUSim/Custom/cards/*/*.php'));
$sites = 0; $bad = [];
foreach ($files as $f) {
    if (str_contains($f, '/SwuPgnWriter/')) continue;
    $lines = file($f);
    foreach ($lines as $i => $line) {
        if (!preg_match('/\bSwuPgnNote(Choice|Damage|Heal|Token|Defeat)?\s*\(/', $line)) continue;
        $sites++;
        if (str_contains($line, 'SwuPgnNoteOn()')) continue;
        // …or inside a block whose opening `if (…SwuPgnNoteOn()…) {` is a few lines above.
        $inBlock = false;
        for ($j = max(0, $i - 4); $j < $i; $j++) if (str_contains($lines[$j], 'SwuPgnNoteOn()') && str_ends_with(rtrim($lines[$j]), '{')) $inBlock = true;
        if (!$inBlock) $bad[] = "$f:" . ($i + 1) . ': ' . trim($line);
    }
}
$ok = $sites > 30 && $bad === [];
echo ($ok ? 'PASS' : 'FAIL') . ": every engine note site ($sites) is guarded by SwuPgnNoteOn()" . ($ok ? '' : "\n  " . implode("\n  ", array_slice($bad, 0, 60))) . "\n";
echo "\n" . ($ok ? 'PASS: test_swupgn_note_sites_guarded.php — all 1 checks passed' : 'FAIL: test_swupgn_note_sites_guarded.php — 1 of 1 checks failed') . "\n";
exit($ok ? 0 : 1);
