<?php
// The collapsed discard PILE must show the most recently discarded card, while OPENING the pile must
// still list the discard from earliest to latest.
//
// PopulateZone's Single branch picks zoneArr[0] unless told otherwise, so the pile showed the FIRST
// card ever discarded and never changed. Sort.Reverse is NOT the lever: UILibraries reverses the whole
// zone array for every mode except Tile (see the "Reverse the zone array" block), which would flip the
// popup's order too — the half that is already correct and must stay that way.
// Instead the Discard zone declares the display parameter `Mode=Single(Latest)` (same mechanism as the
// Deck's `Single(Stacked)`), consumed ONLY inside the Single branch.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$root = __DIR__ . '/../../..';

// --- the schema declares it (the tracked source; the generated files are rebuilt from this) ---
$schema = file_get_contents($root . '/Schemas/SWUSim/GameSchema.txt');
check($schema !== false, 'GameSchema.txt is readable');
check(preg_match('/^Discard - .*$\s*(^(?!Discard).*$\s*)*?^Display:.*Mode=Single\(([^)]*)\)/m', $schema, $m) === 1,
      'the Discard zone declares Mode=Single(<params>)');
check(stripos($m[2], 'Latest') !== false,
      "the Discard zone's Single display asks for the LATEST card (got: {$m[2]})");

// --- the shared UI honours it, in the Single branch only ---
$ui = glob($root . '/Core/UILibraries20*.js');
check(count($ui) === 1, 'exactly one UILibraries bundle');
$js = file_get_contents($ui[0]);
$start = strpos($js, "if(mode == 'Single'");
check($start !== false, "PopulateZone's Single branch is present");
$branch = preg_replace('~//[^\n]*~', '', substr($js, $start, 900));   // assert CODE, not comments
check(strpos($branch, 'displayIndex') !== false, 'the Single branch chooses a displayIndex');
check(preg_match('/Latest/i', $branch) === 1 || strpos($branch, 'ShouldShowLatestInSingleZone') !== false,
      'the Single branch honours the Latest display parameter');

// --- and the popup must NOT be reversed: Sort stays null for Discard ---
check(preg_match('/^Discard - .*$\s*(^(?!Discard).*$\s*)*?^Sort:/m', $schema) !== 1,
      'the Discard zone declares no Sort (so mode=All keeps insertion order, earliest -> latest)');

// --- the regenerated client metadata carries the parameter through ---
$gen = glob($root . '/SWUSim/GeneratedUI_*.js');
if (count($gen) === 1) {
    $g = file_get_contents($gen[0]);
    check(preg_match('/"Name":"Discard".*?"DisplayParameters":\[([^\]]*)\]/s', $g, $dm) === 1,
          'the generated zone metadata has Discard DisplayParameters');
    check(stripos($dm[1], 'Latest') !== false,
          "regenerated metadata carries the Latest parameter (got: [{$dm[1]}]) — rerun zzGameCodeGenerator if this fails");
} else {
    echo "  skip: GeneratedUI_*.js not present/unique here\n";
}
echo "PASS\n";
