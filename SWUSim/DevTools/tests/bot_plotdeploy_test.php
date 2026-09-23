<?php
// Feature 'plotdeploy' — ON THE FLIP TURN, DEPLOY BEFORE YOU SPEND. Owner deck guide 2026-09-16
// (SWUSim/docs/bot-openers-research.md, Ahsoka · Blue 30HP): "ideal flip turn is when you have both the Naboo ship
// and Jar Jar in resources… you first use Ahsoka's ability to buff something, then flip Ahsoka, choose Plot first to
// Plot out both of those Plot cards… At best you are getting +12 damage on flip turn and closing the game here."
//
// Plot (SEC_111 Jar Jar 2, SEC_099 Naboo Royal Starship 4) reads "When you deploy a leader, you may play this card
// from your resources, PAYING ITS COST." Ahsoka's Epic Action deploys at 6 resources and spends none, so the flip
// turn has exactly 6 to spend and the two Plot cards cost exactly 6 — ANY ordinary play made first makes the full
// Plot unaffordable.
//
// Measured over 60 self-play games (ahsoka_blue vs luke_datavault, 2026-09-16): Ahsoka deployed in 42,
// always on round 5 — the right turn — but plotted NOTHING in 24 of those 42. The discriminator was purely the
// ordering: games that plotted had played 0.56 cards before deploying, games that plotted nothing had played 1.75.
// The resourcer is not at fault — SWUBotChooseResourceCards already ranks Plot cards first (keep = -1000). The bot
// banks the Plot cards correctly and then spends the resources that would have paid for them.
//
// So the deploy must be priced by what it ENABLES — the same shape as 'enablers' (_SWUBotDeployDiscount, a deploy
// that makes hand cards cheaper) and 'pilotdeploy' (a deploy that adds power to a ready Vehicle).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_plotdeploy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('no-plotdeploy') === ['plotdeploy'] && in_array('plotdeploy', (array)SWUBotVariantDisabled('no-p3'), true),
    'plotdeploy is switchable, alone and in the part-3 group');

$DEPLOY = 'myLeader-0!CustomInput!DeployLeader:Unit';
$scoreOf = function (string $style, string $cardID) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$bestPlay = function (string $style) use ($botCtx) {
    // The best ordinary hand play on offer — what the deploy has to beat.
    $ctx = $botCtx($style); $best = null;
    foreach ($ctx['actions'] as $i => $a) {
        if (!str_starts_with(strval($a['cardID']), 'myHand-')) continue;
        $s = SWUBotScoreAction($ctx, $a, $i);
        if ($best === null || $s > $best) $best = $s;
    }
    return $best;
};

// The reported board: the flip turn. 6 resources, two of them the Plot cards, two castable 2-drops in hand.
$flipTurn = function ($jarjar = 'SEC_111', $naboo = 'SEC_099') use ($build) {
    $build(function ($b) use ($jarjar, $naboo) {
        $b->MyLeader('ASH_009');                       // undeployed, ready, Epic Action unused
        $b->FillResourcesForPlayer(1, $jarjar, 1);     // Plot, cost 2
        $b->FillResourcesForPlayer(1, $naboo, 1);      // Plot, cost 4
        $b->FillResourcesForPlayer(1, 'SOR_095', 4);   // 6 resources total → Epic Action is live
        $b->WithCardInHandForPlayer(1, 'LOF_093');     // Gungi, 2 — Command/Heroism, no aspect penalty
        $b->WithCardInHandForPlayer(1, 'ASH_056');     // Huyang, 2
        $b->TheirBase('SOR_020', 0);
    });
};

// ⚠ Both sides of each comparison must be read under the SAME feature state: 'plotdeploy' also moves the max-units
// guide's 4.0 from the hand play to the deploy, so a play scored with the feature ON is not the play the old model
// was choosing between.
$flipTurn();
SWUBotSetDisabledFeatures([]);
$deployHot = $scoreOf('aggro', $DEPLOY);
$playHot = $bestPlay('aggro');
$check($deployHot !== null && $playHot !== null, 'fixture: the deploy and at least one hand play are both on offer');
SWUBotSetDisabledFeatures(['plotdeploy']);
$deployCold = $scoreOf('aggro', $DEPLOY);
$playCold = $bestPlay('aggro');
$check($deployCold < $playCold, sprintf('the old model: the deploy (%.2f) lost to a 2-drop (%.2f), spending the Plot budget', $deployCold, $playCold));
SWUBotSetDisabledFeatures([]);
$check($deployHot > $playHot, sprintf('the flip turn now deploys FIRST: %.2f vs %.2f', $deployHot, $playHot));

// ⚠ The two halves must be asserted SEPARATELY. Aggro's max-units guide is worth 4.0 and would carry the ordering
// on its own, so deleting the deploy's Plot VALUE still leaves the aggro checks above green (verified by mutation).
// Normal weights max-units at 0.0, so there the deploy's score is the scorer's work alone.
SWUBotSetDisabledFeatures([]);
$normalHot = $scoreOf('normal', $DEPLOY);
SWUBotSetDisabledFeatures(['plotdeploy']);
$normalCold = $scoreOf('normal', $DEPLOY);
$check($normalHot > $normalCold, sprintf('the deploy is scored by what it plots, guide aside: %.2f vs %.2f', $normalHot, $normalCold));

// ...and the value tracks WHICH cards are affordable: one Plot card banked is worth less than two.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->FillResourcesForPlayer(1, 'SEC_111', 1);     // Jar Jar only — 2 of the 6 budget used
    $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithCardInHandForPlayer(1, 'LOF_093');
    $b->TheirBase('SOR_020', 0);
});
SWUBotSetDisabledFeatures([]);
$oneHot = $scoreOf('normal', $DEPLOY);
$check($oneHot > $normalCold && $oneHot < $normalHot,
    sprintf('one banked Plot card is worth less than two: %.2f, between %.2f and %.2f', $oneHot, $normalCold, $normalHot));

// No Plot card banked → nothing to enable, so the ordering is exactly as before.
$flipTurn('SOR_095', 'SOR_095');
SWUBotSetDisabledFeatures([]);
$noPlotHot = $scoreOf('aggro', $DEPLOY);
SWUBotSetDisabledFeatures(['plotdeploy']);
$check($noPlotHot === $scoreOf('aggro', $DEPLOY), 'no Plot card in resources earns no deploy bonus');

// A Plot card it cannot pay for is worth nothing: 2 resources cannot cast the 4-cost Naboo Starship.
$build(function ($b) {
    $b->MyLeader('ASH_009');
    $b->FillResourcesForPlayer(1, 'SEC_099', 1);      // the only Plot card, cost 4
    $b->FillResourcesForPlayer(1, 'SOR_095', 1);      // 2 resources: Epic Action is dead anyway, but so is the Plot
    $b->WithCardInHandForPlayer(1, 'LOF_093');
    $b->TheirBase('SOR_020', 0);
});
SWUBotSetDisabledFeatures([]);
$brokeHot = $scoreOf('aggro', $DEPLOY);
SWUBotSetDisabledFeatures(['plotdeploy']);
$check($brokeHot === $scoreOf('aggro', $DEPLOY), 'a Plot card it cannot afford earns no deploy bonus');

SWUBotSetDisabledFeatures([]);
bot_test_finish();
