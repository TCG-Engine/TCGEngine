<?php
// Feature 'defeatimmune' (p36) — a unit that "can't be defeated by enemy card abilities" (SWUAvoidsDefeat: JTL_103 Chewbacca,
// alone or as a Pilot, TWI_220 Shadowed Intentions, LAW_149 Rey, …) is not a target for a DEFEAT effect: it fizzles. Reprint_Cad
// (Hemlock Red) vs Ninin (Ahsoka Yellow), human game 2026-10-04: Chewbacca piloted the Sheathipede; Weakness tokens (state-based
// 0 HP) and No Glory, Only Results (take control first) still answer it, Lost and Forgotten / Chimaera / a wipe do not.
// Owner 2026-10-04: build it.
// Fixtures (dictionary-checked): SEC_215 Emissary's Sheathipede 2/4 (space) · JTL_103 Chewbacca (Piloting) · JTL_115 Clone Combat
//   Squadron 3/3 (4) · LAW_133 Lost and Forgotten (6) · SEC_078 Hyperspace Disaster · HMW_003 · HMW_027 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_defeatimmune_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-defeatimmune') === ['defeatimmune'], 'defeatimmune is switchable');
$check(in_array('defeatimmune', SWUBotFeatureGroups()['p36'] ?? [], true), 'defeatimmune is in group p36');
$feat = function (bool $on, callable $f) { $GLOBALS['SWUBotDisabledFeatures'] = $on ? [] : ['defeatimmune']; $r = $f(); $GLOBALS['SWUBotDisabledFeatures'] = []; return $r; };
// Seat 2: Sheathipede piloted by Chewbacca (space 0) and Clone Combat Squadron (space 1). Seat 1: $hand, 7 resources.
$board = function (array $hand) use ($build) {
    $build(function ($b) use ($hand) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', 7);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithSpaceUnitForPlayer(2, 'SEC_215');
        $b->WithSpaceUnitForPlayer(2, 'JTL_115');
        $b->WithUpgradesOnSpaceUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('JTL_103', 2)]);
    });
};

// A) Lost and Forgotten's target: today the piloted Sheathipede (worth more, and immune); fixed, the Squadron.
$target = function (string $variant) use ($board, $act, &$gameName) {
    $board(['LAW_133']);
    $act(1, 10002, 'myHand-0!FSM!');
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$board([]);
$check(SWUAvoidsDefeat(GetSpaceArena(2)[0]), 'A fixture: the Chewbacca-piloted Sheathipede avoids enemy defeat');
$check($target('no-defeatimmune') === 'theirSpaceArena-0', 'A fixture: today L&F aims at the immune Sheathipede; got ' . var_export($target('no-defeatimmune'), true));
$check($target('') === 'theirSpaceArena-1', 'A: L&F takes the Squadron instead; got ' . var_export($target(''), true));

// B) Hyperspace Disaster's enemy losses leave out the unit it cannot defeat.
$board(['SEC_078']);
$sq = SWUBotUnitValue(SWUBotUnits(2)[1]);
[, $theirsOn] = $feat(true, fn() => _SWUBotWipeLosses(1, 'SEC_078'));
[, $theirsOff] = $feat(false, fn() => _SWUBotWipeLosses(1, 'SEC_078'));
$check(abs($theirsOn - $sq) < 1e-9, "B: Disaster's enemy losses are the Squadron alone; got $theirsOn vs $sq");
$check($theirsOff > $theirsOn, "B fixture: today the immune Sheathipede is counted; got $theirsOff");

// C) Chimaera's best enemy pick ignores it; D) a defeat event is not counted as killing it; E) damage still is unaffected.
$check(abs($feat(true, fn() => _SWUBotBestEnemyNonLeaderValue(1)) - $sq) < 1e-9, 'C: the best enemy non-leader to defeat is the Squadron');
$immune = SWUBotUnits(2)[0];
$check($feat(true, fn() => SWUBotHandCardKills('LAW_133', $immune)) === false, 'D: Lost and Forgotten does not "kill" the immune unit');
$check($feat(false, fn() => SWUBotHandCardKills('LAW_133', $immune)) === true, 'D fixture: today it is counted as a kill');
$check(abs($feat(true, fn() => _SWUBotTargetScore(1, 'theirSpaceArena-0', true, 2, 'DEAL_TARGET', SWUBotWeights('softcontrol', 1)))
         - $feat(false, fn() => _SWUBotTargetScore(1, 'theirSpaceArena-0', true, 2, 'DEAL_TARGET', SWUBotWeights('softcontrol', 1)))) < 1e-9,
    'E: a DAMAGE effect on the immune unit is scored exactly as before');

// F) Which continuations DEFEAT: Lost and Forgotten does; No Glory, Only Results takes control first (its defeat is then no
// enemy ability) — it still answers the immune unit, so it is NOT a fizzling defeat.
$check(_SWUBotHeadDefeats('LAW_133#0') && _SWUBotHeadDefeats('DEFEAT_UNIT'), 'F: Lost and Forgotten / DEFEAT_UNIT are defeats');
$check(!_SWUBotHeadDefeats('JTL_043#0'), 'F: No Glory, Only Results (take control, then defeat) is not an enemy defeat');

bot_test_finish();
