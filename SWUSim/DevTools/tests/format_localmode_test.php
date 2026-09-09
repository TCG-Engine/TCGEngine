<?php
// The localMode flag marks formats with no remote opponent (Goldfish = solo; Hotseat = one human
// driving both seats). It is the routing predicate for the waiting room: a localMode format never
// gets a lobby page.
//
// ⚠ SWUGetFormat() returns an explicit key WHITELIST. Renaming the key in SWUFormatDefinitions()
// without renaming it in that whitelist makes EVERY format report localMode=false, which silently
// hands goldfish a waiting room. The whitelist assertions below are the guard for that.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

require_once __DIR__ . '/../../../AppCore/SWU/Formats.php';

check(SWUGetFormat('goldfish')['localMode'] === true,  'goldfish is a localMode format');
check(SWUGetFormat('hotseat')['localMode']  === true,  'hotseat is a localMode format (one human, both seats)');
// Bot Practice is a localMode format too: one human at seat 1, a bot at seat 2, no remote opponent.
// It is currently 'enabled' => false (its menu wiring is Phase 5), which is exactly why the sweep
// below enumerates SWUFormatDefinitions() rather than SWUListFormats() — see the comment there.
check(SWUGetFormat('botpractice')['localMode'] === true, 'botpractice is a localMode format (human + bot, no remote opponent)');
check(SWUGetFormat('premier')['localMode']  === false, 'premier is NOT a localMode format');
check(SWUGetFormat('twinsuns')['localMode'] === false, 'twinsuns is NOT a localMode format');
check(SWUGetFormat('teamsuns')['localMode'] === false, 'teamsuns is NOT a localMode format');

// The whitelist guard: exactly three formats must report TRUE (goldfish, hotseat, botpractice). If
// the rename missed SWUGetFormat's return array, every format reports false — this makes that
// failure mode explicit rather than something only the assertions above happen to catch.
//
// ⚠ Enumerates SWUFormatDefinitions(), NOT SWUListFormats(). SWUListFormats() drops disabled
// formats, so a localMode format that is merely hidden from the menu (botpractice, until Phase 5
// wires it) would silently leave this guard covering one fewer format — and flipping 'enabled' back
// to true would then break a test that had nothing to do with the change. Registered is the right
// population here; the enabled flag is a MENU concern.
$localModeFormats = [];
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    $f = SWUGetFormat($id);
    if ($f !== null && !empty($f['localMode'])) $localModeFormats[] = $id;
}
sort($localModeFormats);
check($localModeFormats === ['botpractice', 'goldfish', 'hotseat'],
      'exactly goldfish+hotseat+botpractice are localMode, got [' . implode(',', $localModeFormats) . '] (whitelist wired?)');

// The old key must be gone, or a stale consumer keeps reading a key nobody sets.
check(!array_key_exists('mode', SWUGetFormat('goldfish')), "the old 'mode' key is gone from SWUGetFormat");

echo "PASS\n";
