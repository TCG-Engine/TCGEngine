<?php
// Feature 'pilotdeploy' — a leader that deploys AS A PILOT onto a READY Vehicle is worth the damage that adds to
// this round's attack, not a flat deploy bonus. Owner report 2026-09-16 (Bot Practice game 469688): with 6
// resources, JTL_009 Boba Fett (upgrade side 4/4) and two ready Vehicles, the bot attacked with BOTH ships and left
// Boba undeployed. Piloting a ready ship first adds +4/+4 to a swing it had not made yet — and Boba's deploy-as-
// upgrade also deals up to 4 damage divided among units.
//
// The scorer could not see it: a deploy is W['deploy'] (1.5 flat) while attacking a base with a 4-power ship is
// W['base'] x 4 = 4.0 for Aggro, so attacking always won and the pilot slot went to waste. This values the deploy
// by what it ENABLES this round — the same shape as feature 'enablers', which already prices a deploy that makes
// hand cards cheaper (_SWUBotDeployDiscount).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_pilotdeploy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('no-pilotdeploy') === ['pilotdeploy'] && in_array('pilotdeploy', (array)SWUBotVariantDisabled('no-p3'), true),
    'pilotdeploy is switchable, alone and in the part-3 group');

$DEPLOY = 'myLeader-0!CustomInput!DeployLeader:Unit';
$scoreOf = function (string $style, string $cardID) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $cardID) return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$pick = function (string $style, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage();
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return strval($p['cardID'] ?? '');
};

// The reported board: Boba (6 resources, Epic Action unused) and a READY 4-power Vehicle with no Pilot on it.
$boardReady = function () use ($build) { $build(function ($b) {
    $b->MyLeader('JTL_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->TheirBase('SOR_020', 0);
    $b->WithSpaceUnitForPlayer(1, 'JTL_240', true); }); };
$boardReady();
SWUBotSetDisabledFeatures([]);
$deployHot = $scoreOf('aggro', $DEPLOY);
$attack = $scoreOf('aggro', 'mySpaceArena-0!FSM!');
$check($deployHot !== null && $attack !== null, 'fixture: the deploy and the attack are both on offer');
SWUBotSetDisabledFeatures(['pilotdeploy']);
$deployCold = $scoreOf('aggro', $DEPLOY);
$check($deployCold < $attack, sprintf('the old model: the deploy (%.2f) lost to the attack (%.2f)', $deployCold, $attack));
SWUBotSetDisabledFeatures([]);
$check($deployHot > $attack, sprintf('piloting a ready Vehicle now beats attacking: %.2f vs %.2f', $deployHot, $attack));
$check($pick('aggro') === $DEPLOY, 'the bot deploys before attacking; got ' . $pick('aggro'));

// An EXHAUSTED Vehicle cannot attack again this round, so the upgrade adds nothing now: no bonus.
$build(function ($b) { $b->MyLeader('JTL_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->TheirBase('SOR_020', 0);
    $b->WithSpaceUnitForPlayer(1, 'JTL_240', false); });
SWUBotSetDisabledFeatures([]);
$exhaustedHot = $scoreOf('aggro', $DEPLOY);
SWUBotSetDisabledFeatures(['pilotdeploy']);
$check($exhaustedHot === $scoreOf('aggro', $DEPLOY), 'an exhausted Vehicle earns no pilot bonus');

// A Vehicle that already carries a Pilot is not a legal destination, so again no bonus. The pilot SEAT is what
// matters, not the upgrade count: GameStateBuilder::Upgrade() writes IsPilot=false, and SWUVehiclePilotCount reads
// exactly that flag — an ordinary upgrade leaves the seat open and the ship is still a legal host.
$pilotUpgrade = GameStateBuilder::Upgrade('JTL_189', 1);
$pilotUpgrade['IsPilot'] = true;
$build(function ($b) use ($pilotUpgrade) { $b->MyLeader('JTL_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->TheirBase('SOR_020', 0); $b->WithSpaceUnitForPlayer(1, 'JTL_240', true);
    $b->WithUpgradesOnSpaceUnitForPlayer(1, 0, [$pilotUpgrade]); });
SWUBotSetDisabledFeatures([]);
$pilotedHot = $scoreOf('aggro', $DEPLOY);
SWUBotSetDisabledFeatures(['pilotdeploy']);
$check($pilotedHot === $scoreOf('aggro', $DEPLOY), 'a Vehicle that already has a Pilot earns no bonus');

// A leader that cannot deploy as an upgrade at all is untouched (SOR_014 has no pilot side).
$build(function ($b) { $b->MyLeader('SOR_014'); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->TheirBase('SOR_020', 0);
    $b->WithSpaceUnitForPlayer(1, 'JTL_240', true); });
SWUBotSetDisabledFeatures([]);
$plainHot = $scoreOf('aggro', $DEPLOY);
SWUBotSetDisabledFeatures(['pilotdeploy']);
$check($plainHot === $scoreOf('aggro', $DEPLOY), 'a leader with no pilot side is scored exactly as before');
SWUBotSetDisabledFeatures([]);

bot_test_finish();
