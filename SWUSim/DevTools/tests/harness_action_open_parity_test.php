<?php
// The test harness must open an action wherever PRODUCTION opens one.
//
// GameTestAdapter calls engine entry points DIRECTLY (SWULeaderAction, SWUDeployLeader, ...), while a
// real game reaches them through CustomInput.php, which calls SaveUndoVersion() first — and that is
// where the action-close ledger stamps a new action id. Where the two routes disagree, a harness action
// inherits the PREVIOUS action's id, its close is refused as a duplicate, and the turn silently never
// passes. The next scripted action then runs out of turn and does nothing.
//
// That cost real debugging time on 2026-08-29 (an attack and a leader ability both ran under id=1, which
// presented as "Osha passes alone but fails in the suite"), and an audit found FOUR more of the same
// shape. This test is what stops the fifth.
//
// ⚠ It compares ROUTES, not behaviour, so it stays green as long as the two entry paths agree. If you
// deliberately add a production entry point that must NOT open an action, add it to $NO_OPEN with a
// reason rather than deleting the check.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root    = __DIR__ . '/../..';                       // SWUSim/
$prod    = file_get_contents($root . '/Custom/CustomInput.php');
$adapter = file_get_contents($root . '/Tests/Framework/GameTestAdapter.php');
check($prod !== false && $adapter !== false, 'both routes are readable');

// Production entry points that deliberately do NOT open an action.
$NO_OPEN = [
    // A pass / initiative claim never opens an action id in production either — SWUPassAction and
    // SWUTakeInitiative do not go through SaveUndoVersion, so the ledger does not model them at all.
    // Closing that hole is a real behaviour change, tracked in docs/action-close-deferrals.md §1.
    'SWUPassAction' => 'production does not stamp a pass either — the ledger does not model passes',
];

// An engine call in CustomInput is "production-stamped" when a SaveUndoVersion sits within the
// preceding few lines of the same case. Window, not whole-file, so an unrelated stamp elsewhere in the
// switch cannot make an unstamped entry point look covered.
$prodLines = explode("\n", $prod);
$stamped   = [];
foreach ($prodLines as $i => $line) {
    if (!preg_match('/\b(SWU[A-Za-z0-9_]+)\s*\(/', $line, $m)) continue;
    $fn = $m[1];
    for ($k = max(0, $i - 10); $k < $i; $k++) {
        if (strpos($prodLines[$k], 'SaveUndoVersion') !== false) { $stamped[$fn] = true; break; }
    }
}
check(!empty($stamped), 'found production entry points that stamp an action open');

// For each, the adapter must open one too — within the same method, before the call.
$adapterLines = explode("\n", $adapter);
$missing = [];
foreach (array_keys($stamped) as $fn) {
    if (isset($NO_OPEN[$fn])) continue;
    foreach ($adapterLines as $i => $line) {
        // ⚠ SKIP COMMENTS. The comments explaining these very stamps NAME the function they guard, so a
        // raw match reports the explanation as an unstamped call site. Same false-positive class as the
        // ActivateCard guard matching a substring inside a longer identifier.
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '//') || str_starts_with($t, '*') || str_starts_with($t, '/*')) continue;
        if (!preg_match('/(?<![A-Za-z0-9_])' . preg_quote($fn, '/') . '\s*\(/', $line)) continue;
        $opened = false;
        for ($k = max(0, $i - 14); $k < $i; $k++) {
            if (strpos($adapterLines[$k], '_SWUOpenAction') !== false) { $opened = true; break; }
            // A call routed through ActionMap goes the production way and stamps on its own.
            if (strpos($adapterLines[$k], 'ActionMap(') !== false)     { $opened = true; break; }
        }
        if (!$opened) $missing[] = $fn . ' @ GameTestAdapter.php:' . ($i + 1);
    }
}

check(empty($missing),
    "every production-stamped entry point also opens an action in GameTestAdapter — add\n"
  . "        `if (function_exists('_SWUOpenAction')) _SWUOpenAction();` before the call, or list it in\n"
  . "        \$NO_OPEN with a reason.\n"
  . "        Missing: " . implode(', ', $missing));

// A $NO_OPEN entry that no longer appears in production is stale — the list must not outlive its
// entries, or it becomes permission nobody remembers granting.
$stale = [];
foreach (array_keys($NO_OPEN) as $fn) {
    if (strpos($prod, $fn) === false) $stale[] = $fn;
}
check(empty($stale), 'every $NO_OPEN entry still exists in production (stale: ' . implode(', ', $stale) . ')');

echo "PASS\n";
