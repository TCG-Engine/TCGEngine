<?php
// The client render seam must emit the DISPLAY printing, for both SWU apps, from the GENERATOR.
// Asserting the generated artifacts as well as the generator, because a correct generator does not
// help if the file on disk is stale — that is exactly how SoulMastersDB served unversioned script
// tags for weeks.
$root = realpath(__DIR__ . '/../..');
$checks = [];

$gen = file_get_contents($root . '/zzGameCodeGenerator.php');
// The generator holds this as an ESCAPED string ("\$obj") because it is emitting PHP source; the
// generated file holds the unescaped form. Assert each in its own vocabulary or the check is a lie.
$checks['generator emits the display id'] = strpos($gen, 'SWUDisplayCardID(\$obj->CardID)') !== false;
// Gated to the SWU apps: GrandArchiveSim / HellbreakSim / SoulMastersDB share this generator and have
// no reprint model at all, so their output must stay byte-identical.
$checks['display seam is gated to SWU roots'] = preg_match('~swuCardApp.*SWUDisplayCardID~s', $gen) === 1;
// The generated entry point is its own HTTP request and includes only what the generator gives it.
// Without this include the substitution is an undefined-function fatal on every board render.
$checks['generator emits the CardDisplayID include'] = strpos($gen, 'AppCore/SWU/CardDisplayID.php') !== false;

// SWUSim's two board-specific display functions must ROUTE THROUGH the shared map rather than
// returning the raw CardID, or the arena and leader slots disagree with every other zone.
$gl = preg_replace('~//[^\n]*~', '', file_get_contents($root . '/SWUSim/Custom/GameLogic.php'));
$checks['arena display routes through the map']  = preg_match('~function SWUArenaDisplayCardID.*?SWUDisplayCardID~s', $gl) === 1;
$checks['leader display routes through the map'] = preg_match('~function SWULeaderDisplayCardID.*?SWUDisplayCardID~s', $gl) === 1;

// The generated artifacts on disk must carry it too.
foreach (['SWUDeck', 'SWUSim'] as $app) {
    $f = $root . "/$app/GetNextTurn.php";
    if (!is_file($f)) { $checks["$app GetNextTurn.php exists"] = false; continue; }
    $src = file_get_contents($f);
    $checks["$app GetNextTurn.php exists"] = true;
    $checks["$app generated seam uses the display id"] = strpos($src, 'SWUDisplayCardID($obj->CardID)') !== false;
    $checks["$app generated file includes the map"]    = strpos($src, 'AppCore/SWU/CardDisplayID.php') !== false;
}

// A non-SWU root must NOT have been given the substitution.
$other = $root . '/GrandArchiveSim/GetNextTurn.php';
if (is_file($other)) {
    $checks['non-SWU root left untouched'] = strpos(file_get_contents($other), 'SWUDisplayCardID') === false;
} else {
    echo "  skip: GrandArchiveSim/GetNextTurn.php not generated here\n";
}

$fail = array_keys(array_filter($checks, fn($v) => !$v));
if ($fail) { fwrite(STDERR, "FAIL (" . count($fail) . "/" . count($checks) . "):\n  - " . implode("\n  - ", $fail) . "\n"); exit(1); }
echo "PASS (" . count($checks) . " checks)\n";
