<?php
// The WithTeams GIVEN directive.
//
// ⚠ THE ONLY ACCEPTED VALUE IS 'true'. Team membership is SEAT PARITY (spec §5): red always holds
// seats 1 and 3, blue 2 and 4, because the lobby assigns seats from each team's fixed slots and
// StartRoom renumbers everyone to table position. There is exactly one possible team arrangement, so
// there is nothing for a spec to express — an earlier draft parsed "P1+P3;P2+P4" and it was deleted as
// a large parser guarding a constant.
//
// What is left worth testing is the REJECTION surface: anything other than 'true' must fail LOUDLY,
// because a silently-accepted bad value builds a fixture whose teams disagree with SWUTeamOf() — a
// section that LOOKS like it set up teams and did not.
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
$root = __DIR__ . '/../../..';
require_once $root . '/SWUSim/Tests/Framework/SchemaTestRunner.php';

function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } echo "  ok: $msg\n"; }

$rm = new ReflectionMethod('SchemaTestRunner', '_assertValidTeamSpec');
$rm->setAccessible(true);
function accepts(ReflectionMethod $rm, string $spec): bool {
    try { $rm->invoke(null, $spec); return true; } catch (Throwable $e) { return false; }
}
function why(ReflectionMethod $rm, string $spec): string {
    try { $rm->invoke(null, $spec); return ''; } catch (Throwable $e) { return $e->getMessage(); }
}

// ── ACCEPTED ────────────────────────────────────────────────────────────────
check(accepts($rm, 'true'), "'true' is accepted");
check(accepts($rm, 'TRUE'), "'true' is case-insensitive");
check(accepts($rm, 'True'), "'True' is accepted");
check(accepts($rm, 'false'), "'false' is accepted as the explicit off switch");
check(accepts($rm, 'FALSE'), "'false' is case-insensitive");

// ── REJECTED — the natural wrong guess, which must TEACH rather than just refuse ──
check(!accepts($rm, 'P1+P2;P3+P4'), 'the natural-but-impossible P1+P2 split is rejected');
$m = why($rm, 'P1+P2;P3+P4');
check(str_contains($m, 'SEAT PARITY'), '  …the message names the rule');
check(str_contains($m, 'seats 1 and 3'), '  …and states which seats each team holds');
check(str_contains($m, 'cannot exist'), '  …and says plainly that the table it describes is impossible');

// Even the CORRECT arrangement is rejected, because writing it out adds nothing.
check(!accepts($rm, 'P1+P3;P2+P4'), 'the correct arrangement is still rejected as redundant');
check(str_contains(why($rm, 'P1+P3;P2+P4'), 'redundant'), '  …and the message says why');

// ── REJECTED — the typo / short-roster cases ────────────────────────────────
check(!accepts($rm, 'P1+P1;P3+P4'), 'a duplicated seat is rejected');
check(!accepts($rm, 'P1+P2;P3'),    'a short team is rejected');
check(!accepts($rm, 'P1+P5;P2+P4'), 'a seat outside P1-P4 is rejected');

// ── REJECTED — everything else ──────────────────────────────────────────────
foreach (['yes', '1', 'on', '', ' ', 'tru', 'truthy', 'P1', 'null'] as $bad) {
    check(!accepts($rm, $bad), "'{$bad}' is rejected");
}

// ⚠ A typo must NOT slip through as "teams off". The apply path validates BEFORE it checks for
// 'false', so a misspelling fails loudly instead of silently building a non-team game — which would be
// the worst outcome, since every team assertion in that section would then quietly test nothing.
check(!accepts($rm, 'fasle'), "a misspelled 'false' is rejected, not treated as off");
check(str_contains(why($rm, 'yes'), "'true' and 'false'"), 'the message names both accepted values');

echo "PASS\n";
