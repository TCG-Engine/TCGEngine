<?php
// Regression (2026-10-03): every decision-queue handler a SWUSim file registers at TOP LEVEL must survive the RUNTIME
// loader. EngineLoadRootRuntime() (Core/EngineActionRunner.php) includes GamestateParser.php → Custom/GameLogic.php from
// INSIDE A FUNCTION, so a top-level `$customDQHandlers[...] = …` lands in that function's LOCAL variable unless the name is
// already bound to the global. GameLogic.php starts the array as a local (`$customDQHandlers = [];`), CardHelpers.php
// registers into it, and CombatLogic.php's top-level `global $customDQHandlers;` then rebinds the name to the global and
// the local registrations are gone. Commit 92c22df3 moved EACH_PLAYER_DEFEAT_PICK (LAW_099 Governor's Shuttle, TWI_238
// Merciless Contest) and HIDDEN_DISCARD_PICK (No Bargain, Every Day More Lies, Guerilla Insurgency, Smoke and Cinders,
// Raze to Ruin) into CardHelpers.php: each fataled ("Value of type null is not callable") the moment it resolved.
// The test-bootstrap path loads the engine at top level, so every other test passed.
// The expected list is READ from the source files, so a handler added anywhere later is covered too.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d display_errors=0 SWUSim/DevTools/tests/runtime_loader_handlers_test.php
chdir('/var/www/html/TCGEngine');
require_once 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('SWUSim');
global $customDQHandlers;

$fails = 0;
$check = function (bool $ok, string $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Every literal key registered at column 0 (top level) under SWUSim/Custom, recursively.
$expected = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('./SWUSim/Custom', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (preg_match_all('/^\$customDQHandlers\[\s*["\']([^"\']+)["\']\s*\]\s*=/m', (string)file_get_contents($f->getPathname()), $m)) {
        foreach ($m[1] as $k) $expected[$k] = str_replace('./SWUSim/Custom/', '', $f->getPathname());
    }
}
$check(count($expected) > 500, 'the scan finds the registered handlers (' . count($expected) . ')');
$check(isset($expected['EACH_PLAYER_DEFEAT_PICK'], $expected['HIDDEN_DISCARD_PICK']), 'the scan sees the two CardHelpers.php handlers');
$missing = [];
foreach ($expected as $k => $file) if (!isset($customDQHandlers[$k])) $missing[] = "$k ($file)";
$check(empty($missing), 'every top-level handler survives the runtime loader' . (empty($missing) ? '' : ' — MISSING: ' . implode(', ', $missing)));

// The SAME trap for EVERY registry, not just $customDQHandlers (2026-10-19, game 1647080): TWI_069 Roger Roger's
// `$upgradeWhenDefeatedAbilities['TWI_069'] = …` is written by cards/_loader.php at GameLogic.php line ~23, but the
// name was only declared `global` further down — so in a live request the registration landed in the loader
// function's local and was dropped, and Roger Roger silently stayed in the discard instead of moving to another
// friendly Battle Droid. The test-bootstrap path loads at top level, so the schema suite passed. Every top-level
// `$name['KEY'] = …` under SWUSim/Custom is read from source and must be in the GLOBAL $name after the runtime loader.
$registries = [];
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') continue;
    if (preg_match_all('/^\$(\w+)\[\s*["\']([^"\']+)["\']\s*\]\s*=/m', (string)file_get_contents($f->getPathname()), $m, PREG_SET_ORDER)) {
        foreach ($m as $hit) {
            if ($hit[1] === 'GLOBALS') continue;
            $registries[$hit[1]][$hit[2]] = str_replace('./SWUSim/Custom/', '', $f->getPathname());
        }
    }
}
$check(isset($registries['upgradeWhenDefeatedAbilities']['TWI_069']), 'the scan sees Roger Roger\'s upgrade When Defeated');
$lost = [];
foreach ($registries as $name => $keys) {
    foreach ($keys as $k => $file) {
        if (!isset($GLOBALS[$name]) || !is_array($GLOBALS[$name]) || !array_key_exists($k, $GLOBALS[$name])) $lost[] = "\${$name}['{$k}'] ({$file})";
    }
}
$check(empty($lost), 'every top-level registry entry (' . count($registries) . ' registries) survives the runtime loader'
    . (empty($lost) ? '' : ' — LOST: ' . implode(', ', array_slice($lost, 0, 20)) . (count($lost) > 20 ? ' …+' . (count($lost) - 20) : '')));

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
