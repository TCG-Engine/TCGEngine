<?php
// Never claim the initiative "for a wipe" when the opponent's READY attackers already have lethal this round (proposal 'claimlethal',
// 2026-10-07 gap screen). FOUND diagnosing Vader (JTL) Yellow vs control (.claude/tmp/diag_vader): Lando s007 R5, at 21/30, "P2 took the
// initiative" as its first action while Vader's ready ships had 10 damage against its 9 HP — dead that round; 9 final-round claims with 3+
// unspent resources in 218 losses. 'initiative-for-wipe' (p36: wipeinit / wipedraw) prices the claim against the round's forgone plays,
// never against surviving it. Same idea as 'lethalrace' (p34) on the attacking side.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_claimlethal_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(in_array('claimlethal', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-claimlethal') === ['try:claimlethal'], 'claimlethal is a switchable proposal');
$stack = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][1] ?? [])];
};
// bot_wipeinit_test's claim board A (Hemlock, 6 resources, Hyperspace Disaster castable next round, HK-47 as the alternative play; Vader
// holds the UNCLAIMED initiative with three ready ships worth 2+2+2 = 6 at my base), with $damage on my base (HMW_027, 30 HP).
$board = function (int $damage) {
    return function ($b) use ($damage) {
        $b->MyLeader('HMW_003', false, false, true); $b->MyBase('HMW_027', $damage);
        $b->TheirLeader('JTL_006', false); $b->WithInitiativePlayerBeing(2);
        $b->FillResourcesForPlayer(1, 'LAW_097', 6);
        foreach (['SEC_078', 'LOF_130'] as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach (array_fill(0, 8, 'SOR_095') as $c) $b->WithCardInDeckForPlayer(1, $c);
        foreach (['SEC_215', 'LAW_135', 'LAW_135'] as $c) $b->WithSpaceUnitForPlayer(2, $c, true);
    };
};
$take = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$build($board(10));
$pot = SWUBotBasePotential(2, 1, true);
$check($pot < 20, "fixture: at 20 HP left their ready ships ($pot) are not lethal");
[$pick, $cov] = $stack('try-claimlethal');
$check($pick === $take && in_array('rule:initiative-for-wipe', $cov, true), 'not lethal: the claim for the wipe stands; got ' . var_export($pick, true));

$build($board(30 - $pot));   // exactly lethal: their ready ships deal my last HP
[$pick, $cov] = $stack();
$check($pick === $take && in_array('rule:initiative-for-wipe', $cov, true), "premise: today the bot claims into lethal at $pot HP; got " . var_export($pick, true));
$build($board(30 - $pot));
[$pick, $cov] = $stack('try-claimlethal');
$check(!in_array('rule:initiative-for-wipe', $cov, true), '@try-claimlethal: no claim for a wipe I will not live to cast; got ' . var_export($pick, true) . ' ' . json_encode($cov));

bot_test_finish();
