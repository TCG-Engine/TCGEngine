<?php
// Feature 'actionclock' (p37) — "am I winning the race?" (SWUBotIsRacing) counted in ACTIONS, not rounds. Owner 2026-10-04, Ninin's
// Ahsoka Yellow losing to Bearr's Han Solo (JTL) Red: "they can do more damage per action even if we have more damage available. we
// have to take more actions to deal all our damage." SWUBotClock divided base HP by summed attack power — a wide board and a tall one
// with equal totals tied — but SWU ALTERNATES actions: the tall side lands its damage in fewer of them.
// Measured first (docs/superpowers/research/2026-09-premier-meta/2026-10-05_action_clock.md): on 22,868 race decisions the two
// verdicts disagree 7.9% of the time, and there the action verdict named the eventual winner 79% of the time (the round verdict
// 21%). Owner chose option A: a shipped feature, measured as its own screen arm.
// The action verdict: every unit that reaches the base (my Sentinels guard their arena; Saboteur ignores) attacks once a round, largest
// first, the two sides alternating, the initiative holder first; whoever empties the other base first wins the race. The round window
// stays (a clock above 3 never races).
// Fixtures (dictionary-checked): ASH_201 Open Circle Ace 2/2 · LAW_149 Rey 9/9 · SOR_095 Battlefield Marine 3/3 · ASH_030 Marrok 2/6
//   Sentinel · HMW_003 · HMW_027 (30) · SOR_020 (30)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_actionclock_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-actionclock') === ['actionclock'], 'actionclock is switchable');
$check(in_array('actionclock', SWUBotFeatureGroups()['p37'] ?? [], true), 'actionclock is in group p37');
// Seat 1's ground units $mine with its base on $myHp; seat 2's $theirs on $theirHp; $init holds the initiative.
$board = function (array $mine, int $myHp, array $theirs, int $theirHp, int $init) use ($build) {
    $build(function ($b) use ($mine, $myHp, $theirs, $theirHp, $init) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027', 30 - $myHp);
        $b->TheirBase('SOR_020', 30 - $theirHp); $b->WithInitiativePlayerBeing($init);
        foreach ($mine as $c) $b->WithGroundUnitForPlayer(1, $c, true);
        foreach ($theirs as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
};
$racing = function (bool $on) { $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['actionclock']; $r = SWUBotIsRacing(1, 2); $GLOBALS['SWUBotDisabledFeatures'] = []; return $r; };
$rank = function (bool $on) { $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['actionclock']; $r = SWUBotRacingRank('softcontrol', 1); $GLOBALS['SWUBotDisabledFeatures'] = []; return $r; };
$wide = array_fill(0, 5, 'ASH_201');   // five 2-power units: 10 in five actions

// A) Bearr's race, from the WIDE side: my five 2s into 10 HP, their Rey (9) into my 9 HP; both round clocks are 1, I hold the
// initiative -> the round verdict says I win. Actions: my 2 (10 -> 8), their 9 kills me. I am NOT winning this race.
$board($wide, 9, ['LAW_149'], 10, 1);
$check(SWUBotClock(1, 2) === 1 && SWUBotClock(2, 1) === 1, 'A fixture: both round clocks are 1');
$check($racing(false) === true, 'A fixture: today the tie on the initiative says "racing"');
$check($racing(true) === false, 'A: counted in actions, the tall side wins — not racing');
$check($rank(true) > $rank(false), 'A: so soft control is NOT shifted toward aggro (rank ' . $rank(false) . ' -> ' . $rank(true) . ')');
// B) The mirror, from the TALL side: my Rey into 9 HP vs their five 2s into my 10, THEY hold the initiative: rounds say I lose the
// tie; actions: their 2 (10 -> 8), my 9 finishes them — I AM winning.
$board(['LAW_149'], 10, $wide, 9, 2);
$check($racing(false) === false, 'B fixture: today the tie off the initiative says "not racing"');
$check($racing(true) === true, 'B: counted in actions, my one 9 lands first — racing');
// C) No disagreement: my 9 into 9 HP vs their three 3s into my 30 — both verdicts say racing.
$board(['LAW_149'], 30, ['SOR_095', 'SOR_095', 'SOR_095'], 9, 2);
$check($racing(false) === true && $racing(true) === true, 'C: both verdicts agree — racing');
// D) The round window stays: a clock above 3 (my 2 into 30 HP) never races, whatever the actions say.
$board(['ASH_201'], 30, [], 30, 1);
$check($racing(true) === false, 'D: my clock is 15 rounds — not racing');
// E) Their Sentinel walls my ground attackers off: they reach nothing, so I am not racing (and they are).
$board($wide, 30, ['ASH_030'], 10, 1);
$check($racing(true) === false, 'E: a Sentinel stops every attack I have — not racing');

bot_test_finish();
