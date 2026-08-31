<?php
// ── EVERY TRIGGER THAT CAN REACH THE EFFECT STACK MUST HAVE A PLAYER-FACING LABEL ────────────────────
//
// The effect stack renders a pill under each card naming the timing window it resolves in. That label
// comes from SWU_EFFECT_STACK_TRIGGER_LABELS in Core/UILibraries<date>.js, and the map used to cover
// **13 of 112** trigger types while falling back to `|| sharedCardData.TriggerType` — so every type it
// did not know printed its DATABASE KEY to the player. Bug report #1024's screenshot caught two of them
// side by side, "SWU_PLOT_WINDOW" and "WHENPLAYEDASUPGRADE", overlapping each other under the cards.
//
// The other ~95 were per-card reactions (SHD_133, ASH_208, LOF_229, …) that had been leaking the same
// way for as long as the effect stack has existed. Nobody reported them because each one needs a
// specific two-trigger board to be seen at all.
//
// ⚠ THIS IS THE POINT OF THE TEST: the fix is a hand-written table, and a hand-written table ROTS.
// Every new card with a reaction adds a trigger type, and without this test the next one silently
// leaks its id again — which is exactly how the original 13-entry map got left behind. The label is
// invisible to the schema suite (it is client-side render text) and to the render suite (which does
// not build a two-trigger effect stack), so a source-shape check is the only thing that can see it.
//
// It compares SOURCES, not behaviour: the set of literal trigger types the engine can put on the stack
// versus the set of keys in the map. Both directions are errors — a missing key leaks an id, and a
// stale key is a label for something that can no longer happen.
//
// Same shape and spirit as harness_action_open_parity_test.php.

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../..';                       // SWUSim/

// ── the map ─────────────────────────────────────────────────────────────────────────────────────────
// Globbed, never hardcoded: the bundle carries a cache-busting datestamp and is renamed on every UI
// ship (see the bump-uilibraries-cache skill). A hardcoded name would make this test pass vacuously
// against a file that no longer exists the moment the stamp moves.
$uiFiles = glob($root . '/../Core/UILibraries*.js');
check(count($uiFiles) === 1,
    'exactly one Core/UILibraries<date>.js bundle (found ' . count($uiFiles) . ': '
    . implode(', ', array_map('basename', $uiFiles ?: [])) . ')');
$ui = file_get_contents($uiFiles[0]);
check($ui !== false, 'the UILibraries bundle is readable (' . basename($uiFiles[0]) . ')');

$start = strpos($ui, 'var SWU_EFFECT_STACK_TRIGGER_LABELS = {');
check($start !== false, 'SWU_EFFECT_STACK_TRIGGER_LABELS is declared in the bundle');
$end = strpos($ui, "\n};", $start);
check($end !== false, 'the label map is terminated');
$mapSrc = substr($ui, $start, $end - $start);

// Keys AND values, so a deliberately-blank entry ('' = "not a timing window, render no badge") stays
// distinguishable from a key that is simply absent. Conflating those is what would let a real omission
// hide behind an intentional one.
preg_match_all("/'([A-Za-z0-9_]+)'\s*:\s*'((?:[^'\\\\]|\\\\.)*)'/", $mapSrc, $mm, PREG_SET_ORDER);
$labels = [];
foreach ($mm as $x) $labels[$x[1]] = $x[2];
check(count($labels) > 50, 'the label map parsed (' . count($labels) . ' entries)');

// ── the engine ──────────────────────────────────────────────────────────────────────────────────────
// Only the two calls that can actually put an entry on the stack, and only LITERAL types — the bag is
// also re-flushed through variables ($t['triggerType'], the Exploit replay), which carry types already
// counted at their original literal call site.
$engine = [];
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Custom'));
foreach ($rii as $f) {
    if ($f->isDir() || substr($f->getFilename(), -4) !== '.php') continue;
    $src = file_get_contents($f->getPathname());
    if ($src === false) continue;
    // AddTrigger($player, 'TYPE', …)  — 2nd argument
    if (preg_match_all("/AddTrigger\s*\(\s*[^,]+,\s*'([A-Za-z0-9_]+)'/", $src, $m1))
        foreach ($m1[1] as $t) $engine[$t] = true;
    // AddEffectStack('CardID', $player, 'TYPE', …) — 3rd argument, positional form only
    if (preg_match_all("/AddEffectStack\s*\(\s*'[^']*'\s*,[^,]+,\s*'([A-Za-z0-9_]+)'/", $src, $m2))
        foreach ($m2[1] as $t) $engine[$t] = true;
}
check(count($engine) > 50, 'found the engine trigger types (' . count($engine) . ')');

// ── both directions ─────────────────────────────────────────────────────────────────────────────────
$missing = array_values(array_diff(array_keys($engine), array_keys($labels)));
sort($missing);
check(empty($missing),
    "every trigger type the engine can put on the effect stack has a label — add it to\n"
  . "        SWU_EFFECT_STACK_TRIGGER_LABELS in Core/UILibraries<date>.js. The badge names the TIMING\n"
  . "        WINDOW the entry resolves in ('When Played', 'When Attack Ends', 'Unit Played'); if the\n"
  . "        thing is not a timing window at all, map it to '' so it renders no badge.\n"
  . "        Missing: " . implode(', ', $missing));

$stale = array_values(array_diff(array_keys($labels), array_keys($engine)));
sort($stale);
check(empty($stale),
    "no label outlives the trigger it names (delete it, or restore the AddTrigger call).\n"
  . "        Stale: " . implode(', ', $stale));

// ── the raw-id fallback must stay gone ──────────────────────────────────────────────────────────────
// Defence in depth for the day this test is skipped: even an unmapped type must render NOTHING rather
// than its id. This is the single line the original bug lived on.
check(strpos($ui, 'sharedCardData.TriggerType;') === false
   && !preg_match('/_labelMap\[[^\]]*\]\s*\|\|/', $ui),
    'the badge has no raw-trigger-id fallback');

// ── self-test ───────────────────────────────────────────────────────────────────────────────────────
// A scanner that cannot fail is not a guard. Prove BOTH directions fire on synthetic input rather than
// trusting that a green run above means the comparison ran at all.
$fakeEngine = array_keys($engine); $fakeEngine[] = 'ZZ_UNMAPPED_999';
check(!empty(array_diff($fakeEngine, array_keys($labels))),
    'self-test: an unmapped trigger type would be flagged');
$fakeLabels = array_keys($labels); $fakeLabels[] = 'ZZ_ORPHAN_999';
check(!empty(array_diff($fakeLabels, array_keys($engine))),
    'self-test: an orphaned label would be flagged');

echo "PASS: effect_stack_labels_test (" . count($engine) . " trigger types, "
   . count(array_filter($labels)) . " labelled, "
   . count(array_filter($labels, fn($v) => $v === '')) . " deliberately badge-less)\n";
