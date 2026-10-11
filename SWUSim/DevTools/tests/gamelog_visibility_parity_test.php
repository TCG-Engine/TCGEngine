<?php
// The game-log visibility rule, and its PARITY with the generated reader.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/gamelog_visibility_parity_test.php
//
// ⚠ WHY PARITY AND NOT JUST BEHAVIOUR. The rule lives in generated SWUSim/GetNextTurn.php, which is
// gitignored and rewritten by zzGameCodeGenerator.php. SWUSim/GetGameLog.php needs the SAME rule for
// a FINISHED game. Two copies of a hidden-information rule is the setup for the Sideboard quietly
// showing a player their opponent's private draws. This test fails the moment they diverge.
error_reporting(E_ALL & ~E_DEPRECATED);
$root = __DIR__ . '/../../..';
require_once $root . '/SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once $root . '/SWUSim/Custom/GameLogEvents.php';

$FAILS = 0;
function check($cond, $msg) { global $FAILS; echo ($cond ? '  ok: ' : '  BAD: ') . "$msg\n"; if (!$cond) $FAILS++; }

$log = implode('<NL>', [
    'PLAY|ALL|P1 played [[SOR_014]]',
    'DRAW|P1|You drew [[SOR_020]]',
    'DRAW|P2|You drew [[SOR_021]]',
    'PEEK|P1,P3|You saw the top card',
    'PHASE|ALL|Regroup phase',
]);

echo "── per-seat visibility ──\n";
// Seat 1 sees: the two ALL lines, its own DRAW|P1, and PEEK|P1,P3 — four. Not the DRAW|P2.
$p1 = SWUFilterGameLogForViewer($log, 1, false);
check(count($p1) === 4, 'seat 1 sees 4 of the 5 lines, got ' . count($p1));
check(in_array('DRAW|P1|You drew [[SOR_020]]', $p1, true), 'seat 1 sees its own draw');
check(!in_array('DRAW|P2|You drew [[SOR_021]]', $p1, true), 'seat 1 does NOT see seat 2 draw');
check(in_array('PEEK|P1,P3|You saw the top card', $p1, true), 'seat 1 is in the multi-seat list');

$p3 = SWUFilterGameLogForViewer($log, 3, false);
check(in_array('PEEK|P1,P3|You saw the top card', $p3, true), 'seat 3 is in the multi-seat list');
check(!in_array('DRAW|P1|You drew [[SOR_020]]', $p3, true), 'seat 3 does not see seat 1 draw');

echo "── a spectator sees ONLY public lines ──\n";
$spec = SWUFilterGameLogForViewer($log, null, true);
check($spec === ['PLAY|ALL|P1 played [[SOR_014]]', 'PHASE|ALL|Regroup phase'],
      'a spectator sees exactly the two ALL lines');
// ⚠ THE LINE ABOVE PINS NOTHING ON ITS OWN, measured by mutation 2026-09-22. A spectator's
// viewerSeat is null, so the tag is 'P0' and matches no restricted line — deleting the $isSpectator
// guard entirely leaves it green. The guard only has an opinion for a viewer who has BOTH a seat
// number and spectator status, so that is what has to be asserted.
check(SWUFilterGameLogForViewer($log, 1, true) === ['PLAY|ALL|P1 played [[SOR_014]]', 'PHASE|ALL|Regroup phase'],
      '★ a spectator holding seat 1 still sees only the ALL lines (the $isSpectator guard itself)');

echo "── an absent visibility field defaults to ALL ──\n";
// ⚠ ASKED AS SEAT 2, NOT SEAT 1. The generated reader's default is 'ALL'; mutating it to 'P1' is
// invisible to seat 1, which would match 'P1' anyway. Seat 2 is the viewer that can tell the two
// apart — measured by mutation 2026-09-22.
check(SWUFilterGameLogForViewer('justtext', 2, false) === ['justtext'],
      '★ a malformed line is public — visible to a seat it does not name');
check(SWUFilterGameLogForViewer('justtext', 1, false) === ['justtext'], 'and to seat 1');

echo "── PARITY with generated GetNextTurn.php ──\n";
// Re-run the generated file's own expression over the same fixture and require an identical answer.
$gen = file_get_contents($root . '/SWUSim/GetNextTurn.php');
check(strpos($gen, "\$logVis === 'ALL'") !== false,
      'the generated reader still uses the rule this helper mirrors (if this fails, READ GetNextTurn and re-derive the helper)');
foreach ([[1, false], [2, false], [3, false], [null, true]] as [$seat, $isSpec]) {
    $expected = [];
    $vSeatTag = 'P' . intval($seat);
    foreach (explode('<NL>', $log) as $entry) {
        $logVis = explode('|', $entry, 3)[1] ?? 'ALL';
        if ($logVis === 'ALL' || (!$isSpec && in_array($vSeatTag, array_map('trim', explode(',', $logVis)), true))) $expected[] = $entry;
    }
    check(SWUFilterGameLogForViewer($log, $seat, $isSpec) === $expected,
          'parity for seat ' . var_export($seat, true) . ' spectator=' . var_export($isSpec, true));
}

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
