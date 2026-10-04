<?php
// Bug #1126 (game 1485163, 2026-10-04) — a damage SPLIT that can finish a base takes the win. Round 12: the bot (Hemlock) cast
// JTL_143 Devastator ("You assign all indirect damage you deal to opponents. When Played: Deal 4 indirect damage to each
// opponent") against a Krennic base on 4 HP (LAW_021, 27 HP, 23 damage), and assigned 3 to Latts Razzi and 1 to Lepi Lookout —
// two kills — instead of all 4 to the base. _SWUBotSplitScore priced base damage W['base'] a point and a kill W['kill'] x value,
// with no lethal check, so the kills outscored the win. Feature 'splitlethal' (p33).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_splitlethal_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-splitlethal') === ['splitlethal'], 'splitlethal is switchable; got ' . json_encode(SWUBotVariantDisabled('no-splitlethal')));
$check(in_array('splitlethal', SWUBotFeatureGroups()['p33'] ?? [], true), 'splitlethal is in group p33');

$legal = fn() => SWUBotLegalActions($GLOBALS['gameName'], 1);
// The bot casts Devastator (hand index 0) and answers its split; returns [their base HP left, the split it chose].
$cast = function (int $theirDamage, string $variant) use ($build, $act, $legal) {
    $build(function ($b) use ($theirDamage) {
        $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', 8);
        $b->WithCardInHandForPlayer(1, 'JTL_143');
        $b->TheirBase('LAW_021', $theirDamage);
        $b->WithGroundUnitForPlayer(2, 'LAW_039');   // Latts Razzi
        $b->WithGroundUnitForPlayer(2, 'LAW_038');   // Lepi Lookout
    });
    $act(1, 10002, 'myHand-0!FSM!');
    $split = '';
    for ($i = 0; $i < 4; $i++) {
        $l = $legal();
        if (($l['kind'] ?? '') !== 'decision') break;
        $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
        if (($l['decisionType'] ?? '') === 'MZSPLITASSIGN') $split = strval($p['cardID'] ?? '');
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID'] ?? ''));
    }
    return [SWUBaseRemainingHp(2), $split];
};

// A) THE REPORT: 4 HP left, 4 indirect to assign. Today: the two kills. Fixed: all 4 to the base — the game.
[$hpOff, $splitOff] = $cast(23, 'no-splitlethal');
$check($hpOff > 0 && $splitOff !== '', 'A fixture: today the bot spends the 4 on units and the base survives; got HP ' . $hpOff . ' split ' . $splitOff);
[$hp, $split] = $cast(23, '');
$check($hp <= 0, 'A: all 4 go to the base and it is defeated; got HP ' . $hp . ' split ' . $split);
// B) 5 HP left: no lethal — the split is scored exactly as before (the kills).
[$hpB, $splitB] = $cast(22, '');
[$hpBoff, $splitBoff] = $cast(22, 'no-splitlethal');
$check($splitB === $splitBoff, 'B: no lethal on offer — unchanged; got ' . $splitB . ' vs ' . $splitBoff);

bot_test_finish();
