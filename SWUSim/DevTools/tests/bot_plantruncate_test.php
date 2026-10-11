<?php
// Feature 'plantruncate' (p42) — a lookahead plan step whose answer list was CUT SHORT is not followed blindly.
// _SWUBotLookaheadContinue() (BotLookahead.php) tries only as many answers as its budget share allows — often 1-2 at depth — and the
// bridge lists MY units first. So a hostile prompt's enemy targets were never tried, the one answer that was (my own unit) became the
// plan, and SWUBotRulePlannedAnswer followed it. Leader audit 2026-10-08: LOF_002 Talzin's -1/-1 on her own units 120 times, LOF_009
// Darth Maul's pings ~340, LAW_013 Chewbacca's deal-2 43 — all by 'planned-answer', still on current code (5 in 18 probe games).
// The existing 'tied' guard did not help: an answer that was never tried cannot tie. Fixed: a step records 'truncated' when answers
// were left untried, and the planned-answer rule lets the fallback choose among ALL the answers then (it aims damage at enemies).
// Fixtures (dictionary-checked): LOF_009 Darth Maul (front: "Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to a
//   different unit") · LOF_020 base · SOR_095 Battlefield Marine (mine, 3 HP) · SEC_T01 Spy (theirs, 0/2) · SOR_225 TIE/ln (theirs, 2/1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_plantruncate_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-plantruncate') === ['plantruncate'], 'plantruncate is switchable');
$check(in_array('plantruncate', SWUBotFeatureGroups()['p42'] ?? [], true), 'plantruncate is in group p42');

$ability = ['playerID' => 1, 'mode' => 10001, 'cardID' => 'myLeader-0!CustomInput!LeaderAbility'];
// Maul's front side with the Force; three of my Marines (listed first at his prompt), their 1-HP TIE/ln in space.
$board = function () use ($build) {
    $build(function ($b) {
        $b->MyLeader('LOF_009', true); $b->MyBase('LOF_020'); $b->WithForceForPlayer(1); $b->WithCurrentRoundBeing(4);
        for ($k = 0; $k < 3; $k++) $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
        $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
// The rule-4 read and score (their clock, mine) — a ping moves neither, so the line has no reason to prefer an answer.
$read = fn() => ['oppClock' => SWUBotClock(2, 1), 'myClock' => SWUBotClock(1, 2)];
$score = fn(array $r) => $r['oppClock'] * 1000 + ($r['oppClock'] - $r['myClock']);

// A) The real lookahead on a tight budget (2: the move + one answer) tries only the first-listed answer — my own Marine.
$board();
$line = SWUBotLookaheadBest(1, $ability, $read, $score, SWU_BOT_LOOKAHEAD_DEPTH, 2);
$step = $line['_path'][0] ?? [];
$check(str_starts_with(strval($step['answer'] ?? ''), 'myGroundArena-'), 'A fixture: the cut-short line plans a ping on my own unit; got ' . json_encode($step));
$check(!empty($step['truncated']), 'A: the step records that answers were left untried; got ' . json_encode($step));

// B) That plan, then the real prompt: today the planned answer (my own Marine) is taken; fixed, the fallback aims at an enemy.
$answerWith = function (string $variant) use ($board, $line, $act, &$gameName, $ability) {
    $board();
    $act(1, 10001, $ability['cardID']);
    $GLOBALS['SWUBotPlan'][1] = $line['_path'];
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
$check(str_starts_with($answerWith('no-plantruncate'), 'myGroundArena-'), 'B fixture: today the planned self-ping is followed');
$got = $answerWith('');
$check(str_starts_with($got, 'their'), 'B: the ping goes to an enemy unit; got ' . $got);

// C) A step that tried EVERY answer is still followed (the plan is only overruled where it could not see).
$board();
$full = SWUBotLookaheadBest(1, $ability, $read, $score, SWU_BOT_LOOKAHEAD_DEPTH, 32);
$check(empty(($full['_path'][0] ?? [])['truncated']), 'C: with the budget to try every answer, the step is not marked truncated; got ' . json_encode($full['_path'][0] ?? null));

bot_test_finish();
