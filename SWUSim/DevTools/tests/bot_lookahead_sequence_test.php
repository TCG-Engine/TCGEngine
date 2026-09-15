<?php
// Phase 1b Task 1 — the SEQUENCE form of the lookahead (SWUSim/Custom/BotLookahead.php, SWUBotLookaheadBest).
// A move, then the acting seat's own follow-up answers, all inside one save/restore; the best line by a score.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lookahead_sequence_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
global $gRandomCounter;

// The same isolation reads as bot_lookahead_test.php: serialized zones + RNG counter, and the Versions zones
// (the undo log and cursor), which GetSerializedZones() excludes.
$snapshot = function () { global $gRandomCounter; return Versions::GetSerializedZones() . '<v0>' . $gRandomCounter; };
$undoState = function () {
    $out = [];
    for ($p = 1; $p <= 4; $p++) {
        $out[] = implode("\n", array_map(fn($e) => $e === null ? '~' : (strval($e->Version) . '|' . intval(!empty($e->removed))), GetVersions($p)));
    }
    return implode("\n--\n", $out) . "\ncursor=" . UndoCursor() . "\ncount=" . UndoStackCount();
};

// ── A play, then the seat's own answers, inside one save/restore ─────────────────────────────────────
// Vanquish (SOR_078, "Defeat a non-leader unit", Vigilance: 5 +2 = 7 for seat 1) stops at its target prompt as a
// single move; SWUBotLookaheadBest() answers it too, keeping the answer the score rates highest.
$vanquishBoard = function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 7); $b->WithCardInHandForPlayer(1, 'SOR_078');
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true);   // Wampa  → theirGroundArena-0
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);   // Marine → theirGroundArena-1
};
$p2ids = fn() => array_values(array_map(fn($u) => strval($u->CardID), array_filter(GetGroundArena(2), fn($u) => empty($u->removed))));
$play = ['playerID' => 1, 'mode' => 10002, 'cardID' => 'myHand-0!FSM!'];
$build($vanquishBoard);
$before = $snapshot(); $beforeUndo = $undoState();
$kill = fn(string $cid) => fn(array $r) => in_array($cid, $r['p2'], true) ? 0.0 : 1.0;   // score: is $cid dead?
$line = SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_164'));
$check($line !== null && $line['p2'] === ['SOR_095'], 'sequence: the play AND its target answer resolve inside — the Wampa is dead; got ' . json_encode($line));
$check(($line['_path'][0]['answer'] ?? '') === 'theirGroundArena-0' && ($line['_path'][0]['tooltip'] ?? '') !== '', 'sequence: the path records the answer that won (the Wampa) and its prompt');
$check(($line['_score'] ?? -1) === 1.0, 'sequence: the line carries its score');
$line = SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_095'));
$check(($line['_path'][0]['answer'] ?? '') === 'theirGroundArena-1' && $line['p2'] === ['SOR_164'], 'sequence: a different score picks the other answer (the Marine)');
$check($snapshot() === $before && $undoState() === $beforeUndo && count($p2ids()) === 2, 'sequence: the live game is byte-identical afterwards, undo untouched, both units alive');
$line = SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_164'), 0);
$check($line !== null && count($line['p2']) === 2 && $line['_path'] === [], 'depth 0 is the single-move lookahead: the prompt is left pending, nothing dies');

// ── The node budget: a line never spends more lookaheads than it is given ───────────────────────────
// Sweep run 3 (2026-09-13) timed out twice: rule 4, facing lethal, abstained only after 17-62 s of searching
// every candidate's every answer three prompts deep. $GLOBALS['SWUBotLookaheadCalls'] counts SWUBotLookahead().
$calls = function (callable $f) { $GLOBALS['SWUBotLookaheadCalls'] = 0; $r = $f(); return [$r, intval($GLOBALS['SWUBotLookaheadCalls'])]; };
$build($vanquishBoard);
[$line, $n] = $calls(fn() => SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_095')));
$check($n === 3 && ($line['_path'][0]['answer'] ?? '') === 'theirGroundArena-1', 'default budget: the play and both answers — 3 lookaheads; got ' . $n);
[$line, $n] = $calls(fn() => SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_095'), SWU_BOT_LOOKAHEAD_DEPTH, 2));
$check($n === 2 && ($line['_path'][0]['answer'] ?? '') === 'theirGroundArena-0', 'a budget of 2: the play and only the first answer (the Wampa), though the score wants the Marine; got ' . $n);
[$line, $n] = $calls(fn() => SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_164'), SWU_BOT_LOOKAHEAD_DEPTH, 1));
$check($n === 1 && $line['_path'] === [] && count($line['p2']) === 2, 'a budget of 1: the play alone, its prompt left pending');

// ── timing ───────────────────────────────────────────────────────────────────────────────────────────
$build($vanquishBoard);
$times = [];
for ($i = 0; $i < 10; $i++) {
    $t0 = hrtime(true);
    SWUBotLookaheadBest(1, $play, fn() => ['p2' => $p2ids()], $kill('SOR_164'));
    $times[] = (hrtime(true) - $t0) / 1e6;
}
sort($times);
$median = ($times[4] + $times[5]) / 2;
echo sprintf("sequence lookahead (play + 2-answer prompt) over 10 runs: median %.2f ms\n", $median);
$check($median < 50.0, 'sequence lookahead stays under the plan\'s 50 ms bar');

bot_test_finish();
