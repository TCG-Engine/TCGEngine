<?php
// ── AN EMPTY ABILITY STUB MUST BE REGISTERED, OR EVERY USE LOGS A FALSE "had no effect" ──────────────
//
// gamelog-updates #2 (2026-09-11): the log writes "P1's X had no effect" when an ability's closure changes
// nothing. A few cards register an intentionally EMPTY closure because their effect is applied elsewhere
// (IBH_010/IBH_042 Han Solo, LOF_014 Grand Inquisitor, SHD_216 Chain Code Collector: the defender's -N/-0
// is set in ExecuteSWUAttack). Those closures always "change nothing", so each one is registered at the stub
// ($swuLogEffectAppliedElsewhere['CARD'] = true) and SWULogNoEffectCheck skips it.
//
// This guard finds every empty ability closure in the card files and requires its registration — so the
// next empty stub fails here instead of printing a false line in every game. Run in the container:
//     php -d xdebug.mode=off SWUSim/DevTools/tests/gamelog_noeffect_stub_test.php

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = realpath(__DIR__ . '/../../..');
$pat = '/\$(\w+Abilities)\["([A-Z0-9_]+):0"\]\s*=\s*(?:\s*\$\w+Abilities\["([A-Z0-9_]+):0"\]\s*=\s*)?function\s*\([^)]*\)\s*\{\s*(?:\/\*.*?\*\/|\/\/[^\n]*\n)?\s*\};/s';

// Self-test on the two shapes that exist.
check(preg_match_all($pat, '$onAttackAbilities["X_001:0"] = function($player, $mzID) { /* elsewhere */ };') === 1, 'scanner finds a one-line empty stub');
check(preg_match($pat, "\$onAttackAbilities[\"X_001:0\"] =\n\$onAttackAbilities[\"X_002:0\"] = function(\$p, \$m) { };", $mm) === 1 && $mm[3] === 'X_002', 'scanner finds a chained (reprint) stub and both ids');
check(preg_match($pat, '$onAttackAbilities["X_001:0"] = function($player, $mzID) { DoThing(); };') === 0, 'a closure with a body is not a stub');

$missing = [];
$found = 0;
foreach (array_merge(glob("$root/SWUSim/Custom/cards/*/*.php"), glob("$root/SWUSim/Custom/*.php")) as $f) {
    $src = file_get_contents($f);
    if (!preg_match_all($pat, $src, $ms, PREG_SET_ORDER)) continue;
    foreach ($ms as $m) {
        foreach (array_filter([$m[2], $m[3] ?? '']) as $id) {
            $found++;
            if (!preg_match('/\$swuLogEffectAppliedElsewhere\[\'' . preg_quote($id, '/') . '\'\]/', $src))
                $missing[] = str_replace($root . '/', '', $f) . " — {$id}";
        }
    }
}
check($found > 0, "found {$found} empty ability stub(s)");
check(empty($missing), 'every empty ability stub is registered in $swuLogEffectAppliedElsewhere' . (empty($missing) ? '' : ":\n    " . implode("\n    ", $missing)));
echo "PASS\n";
