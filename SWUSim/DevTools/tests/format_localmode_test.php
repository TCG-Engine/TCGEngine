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
check(SWUGetFormat('premier')['localMode']  === false, 'premier is NOT a localMode format');
check(SWUGetFormat('twinsuns')['localMode'] === false, 'twinsuns is NOT a localMode format');
check(SWUGetFormat('teamsuns')['localMode'] === false, 'teamsuns is NOT a localMode format');

// The whitelist guard: exactly two formats must report TRUE. If the rename missed SWUGetFormat's
// return array, every format reports false — this makes that failure mode explicit rather than
// something only the first two assertions happen to catch.
$trueCount = 0;
foreach (array_keys(SWUListFormats()) as $id) {
    $f = SWUGetFormat($id);
    if ($f !== null && !empty($f['localMode'])) $trueCount++;
}
check($trueCount === 2, "exactly 2 formats are localMode, got $trueCount (whitelist wired?)");

// The old key must be gone, or a stale consumer keeps reading a key nobody sets.
check(!array_key_exists('mode', SWUGetFormat('goldfish')), "the old 'mode' key is gone from SWUGetFormat");

echo "PASS\n";
