<?php
// The multiplayer table's second column (plan 2026-11-21-swusim-multiplayer-table Task 2): the generated render defines
// SWURenderSeatPair(responseArr, bottomSeat, topSeat), which draws two more seats into seat-named slots (#p{n}<Slot>) with
// seat-named zones (PopulateZone('p{n}<Zone>') → card ids p{n}<Zone>-{i}, the engine's own target ids), and calls it only when
// the page is a table. Reads the GENERATED file — regenerate first: php zzGameCodeGenerator.php rootName=SWUSim.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/table_pair_render_gen_test.php
$root = dirname(__DIR__, 3);
// The generated file is PHP echo('…') lines: read the JS they print (quotes are escaped as \' in the source).
$gen = str_replace("\\'", "'", (string)@file_get_contents("$root/SWUSim/NextTurnRender.php"));
$schema = (string)@file_get_contents("$root/Schemas/SWUSim/GameSchema.txt");
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// The per-seat zones bound to a slot, from the schema itself (no literal list): a zone header line "Name - Field:type…" followed
// by its Display line with BindTo=…Slot, excluding Scope=Global.
$zones = [];
$cur = null;
foreach (preg_split('/\R/', $schema) as $line) {
    if (preg_match('/^([A-Z][A-Za-z0-9]+) - /', $line, $m)) $cur = $m[1];
    if ($cur !== null && preg_match('/^Display:.*BindTo=([A-Za-z]+Slot)/', $line, $m) && !preg_match('/Scope=Global/i', $line)) $zones[$cur] = $m[1];
}
$check(count($zones) >= 8, 'fixture: the schema binds the per-seat zones to slots (' . implode(',', array_keys($zones)) . ')');

$check(substr_count($gen, 'function SWURenderSeatPair(') === 1, 'NextTurnRender.php defines SWURenderSeatPair exactly once');
$check((bool)preg_match('/if\s*\(window\.SWUTableLayout\s*&&\s*window\.SWUTableLayout\.kind\)\s*SWURenderSeatPair\(responseArr,\s*window\.SWUTableLayout\.seating\.right\.bottom,\s*window\.SWUTableLayout\.seating\.right\.top\)/', $gen),
    'the render calls it only on a table page, for the right column');
$check(strpos($gen, "SWURenderSeatPair(responseArr, window.SWUTableLayout.seating.right.bottom, window.SWUTableLayout.seating.right.top); if (typeof window.swuTableAfterSeatPair === 'function') window.swuTableAfterSeatPair();") !== false,
    'right after drawing the column, the page gets its hook (swuTableAfterSeatPair: display-only + targets)');
$body = '';
if (preg_match('/function SWURenderSeatPair\(.*?\/\* end SWURenderSeatPair \*\//s', $gen, $m)) $body = $m[0];
$check($body !== '', 'the function body is marked (/* end SWURenderSeatPair */) so it can be inspected');
foreach ($zones as $zone => $slot) {
    $check(strpos($body, "PopulateZone('p' + _s + '$zone'") !== false, "$zone renders with a seat-named zone (p{n}$zone)");
    $check(strpos($body, "'p' + _s + '$slot'") !== false, "$zone renders into the seat-named slot p{n}$slot");
}
// It must never touch the pair the main render owns, or the selection.
foreach (['myStuff', 'theirStuff', 'myRows', 'theirRows', 'ClearSelectionMode', 'PlaySound', 'Animation'] as $forbidden) {
    $check(strpos($body, $forbidden) === false, "SWURenderSeatPair does not touch $forbidden");
}

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
