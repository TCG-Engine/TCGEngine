<?php
// LoadDeck is the single egress point for every export path — the JSON copy button, the share link and
// the public endpoint all pass through $emitID. It must emit the DISPLAY printing in both id modes.
// format=sha256 must NOT move: it hashes the stored id, and changing it changes every deck hash in
// circulation.
$root = realpath(__DIR__ . '/../..');
$src  = file_get_contents($root . '/APIs/LoadDeck.php');
$code = preg_replace('~//[^\n]*~', '', $src);   // assert CODE, not the comments that name the function
$checks = [];

$checks['emitID applies the display map'] = preg_match('~\$emitID\s*=.*?SWUDisplayCardID~s', $code) === 1;
// Applied BEFORE the SET_NNN/UID branch, so both output modes get it.
$checks['applied before the UID branch']  = preg_match('~SWUDisplayCardID.*?UUIDLookup~s', $code) === 1;
$checks['LoadDeck requires the map']      = strpos($code, 'CardDisplayID.php') !== false;

// The sha256 branch must still hash the raw stored CardID.
$sha = strstr($code, 'sha256');
$checks['sha256 branch does not use the display map'] =
    $sha === false || strpos(substr($sha, 0, 900), 'SWUDisplayCardID') === false;

// Behavioural: the map itself must actually move a known reprinted card, or the wiring above is
// decorative. Guards against the include silently resolving to a no-op function.
require_once $root . '/SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/AppCore/SWU/CardDisplayID.php';
$checks['display map moves Wampa'] = SWUDisplayCardID('SOR_164') === 'LOF_164';

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
