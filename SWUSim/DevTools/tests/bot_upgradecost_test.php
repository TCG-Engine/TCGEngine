<?php
// Feature 'upgradecost' (p36) — an upgrade is worth the card it is, not a flat +1. Reprint_Cad (Hemlock Red) vs Ninin (Ahsoka
// Yellow), human game 2026-10-04, R4: No Glory, Only Results took Open Circle Ace WITH Han Solo piloting it — two cards, one of
// them a 5-drop. _SWUBotValuedUpgrades counted every non-downgrade subcard as 1, a Shield token and Han Solo alike. Owner
// 2026-10-04: build it. A TOKEN upgrade (Shield, Experience) stays 1; a downgrade (Weakness, an enemy-owned Condemn) stays 0;
// any other upgrade counts its printed cost.
// Fixtures (dictionary-checked): ASH_201 Open Circle Ace 2/2 (1, space) · JTL_203 Han Solo (5, Piloting) · JTL_115 Clone Combat
//   Squadron 3/3 (4, space) · JTL_043 No Glory, Only Results (5) · SOR_T02 Shield · HMW_T02 Weakness · SEC_038 Condemn (3) ·
//   HMW_003 Doctor Hemlock · HMW_027 Bioweapons Lab · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_upgradecost_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-upgradecost') === ['upgradecost'], 'upgradecost is switchable');
$check(in_array('upgradecost', SWUBotFeatureGroups()['p36'] ?? [], true), 'upgradecost is in group p36');
$val = function (array $v, bool $on) {
    $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['upgradecost'];
    $x = SWUBotUnitValue($v); $GLOBALS['SWUBotDisabledFeatures'] = []; return $x;
};
// Seat 2: Open Circle Ace carrying $upgrades (space 0) and Clone Combat Squadron (space 1). Seat 1: NGOR in hand, 5 resources.
$board = function (array $upgrades) use ($build) {
    $build(function ($b) use ($upgrades) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', 5);
        $b->WithCardInHandForPlayer(1, 'JTL_043');
        $b->WithSpaceUnitForPlayer(2, 'ASH_201');
        $b->WithSpaceUnitForPlayer(2, 'JTL_115');
        if ($upgrades) $b->WithUpgradesOnSpaceUnitForPlayer(2, 0, $upgrades);
    });
    return SWUBotUnits(2)[0];
};

// A) Values: a pilot counts its cost; a Shield token counts as before; a Weakness and an enemy Condemn count nothing.
$ace = $board([]);                                       $bare = $val($ace, true);
$han = $board([GameStateBuilder::Upgrade('JTL_203', 2)]);
$check(SWUBotUnits(2)[0]['upgrades'] === 1, 'A fixture: Han Solo is attached to the Ace');
$check(abs($val($han, true) - ($bare + 5)) < 1e-9, 'A: Han Solo (cost 5) adds 5; got ' . ($val($han, true) - $bare));
$check(abs($val($han, false) - ($bare + 1)) < 1e-9, 'A fixture: today Han Solo adds a flat 1; got ' . ($val($han, false) - $bare));
$sh = $board([GameStateBuilder::Upgrade('SOR_T02', 2)]);
$check(abs($val($sh, true) - $val($sh, false)) < 1e-9, 'A: a Shield token is valued exactly as before');
$wk = $board([GameStateBuilder::Upgrade('HMW_T02', 2)]);
$check(abs($val($wk, true) - $val($wk, false)) < 1e-9, 'A: a Weakness token is valued exactly as before (nothing)');
$cd = $board([GameStateBuilder::Upgrade('SEC_038', 1)]);
$check(abs($val($cd, true) - $val($cd, false)) < 1e-9 && abs($val($cd, true) - ($bare + 1)) < 1e-9,
    'A: a Condemn seat 1 put on the Ace keeps its flat 1 (only the host\'s own attachments are re-priced); got ' . ($val($cd, true) - $bare));

// B) The R4 decision: No Glory, Only Results — the piloted Ace (two cards) over the bigger Clone Combat Squadron.
$target = function (string $variant) use ($board, $act, &$gameName) {
    $board([GameStateBuilder::Upgrade('JTL_203', 2)]);
    $act(1, 10002, 'myHand-0!FSM!');
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$check($target('no-upgradecost') === 'theirSpaceArena-1', 'B fixture: today NGOR takes the Squadron; got ' . var_export($target('no-upgradecost'), true));
$check($target('') === 'theirSpaceArena-0', 'B: NGOR takes the Ace and Han Solo with it; got ' . var_export($target(''), true));

bot_test_finish();
