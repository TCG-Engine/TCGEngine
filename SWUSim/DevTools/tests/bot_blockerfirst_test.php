<?php
// 'blockerfirst' PROMOTED to a shipped feature (p37) for every style — owner 2026-10-05: "promote it for all. hyperaggro may not even
// play sentinels." Fidelity screen 2026-10-05 (docs/superpowers/research/2026-09-premier-meta/2026-10-04_fidelity_levers.md):
// @try-blockerfirst moved four styles toward the real matchup table (softcontrol +2.5, softaggro +2.3, midrange +1.9, hardcontrol
// +1.2 pp), 652 of 2,920 games changed. It supersedes the midrange-only 2026-09-23 ruling ('mgsentinel', still a proposal).
// The rule: behind on BODIES, with an attack available, play a unit before attacking (BotRules.php SWUBotRuleBlockerFirst).
// '@no-blockerfirst' switches it off; '@try-blockerfirst' still names it (bot_behaviour_arms_test registers it).
// Fixtures: SOR_014 Sabine Wren (exhausted, Epic used) · SOR_095 Battlefield Marine 3/3 · LOF_084 Knight of Ren 4/4
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_blockerfirst_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-blockerfirst') === ['blockerfirst'], 'blockerfirst is a shipped, switchable feature');
$check(in_array('blockerfirst', SWUBotFeatureGroups()['p37'] ?? [], true), 'blockerfirst is in group p37');
$check(SWUBotVariantDisabled('try-blockerfirst') === ['try:blockerfirst'], 'the @try- name still resolves');
$rule = (SWUBotRulesAfterFilter())['blocker-first'];
// Seat 1: $mine Marines on board + a Marine in hand, 6 resources; seat 2: $theirs Knights of Ren.
$behind = function (int $mine, int $theirs) use ($build) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('SOR_014', false, false, true); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        for ($i = 0; $i < $mine; $i++) $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        for ($i = 0; $i < $theirs; $i++) $b->WithGroundUnitForPlayer(2, 'LOF_084', true);
        $b->WithCardInHandForPlayer(1, 'SOR_095');
    });
};
$fire = function (string $style, array $disabled) use ($rule, $botCtx) {
    SWUBotSetDisabledFeatures($disabled); $p = $rule($botCtx($style)); SWUBotSetDisabledFeatures([]);
    return $p === null ? null : strval($p['cardID']);
};
// A) ON BY DEFAULT, in every style: behind 1 body to 3, the body goes down before the attack.
$behind(1, 3);
foreach (['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'] as $st)
    $check($fire($st, []) === 'myHand-0!FSM!', "A: $st, default stack — body first; got " . var_export($fire($st, []), true));
// B) '@no-blockerfirst' turns it off.
$check($fire('softcontrol', ['blockerfirst']) === null, 'B: @no-blockerfirst — the rule abstains');
// C) Ahead on bodies: abstains.
$behind(3, 1);
$check($fire('softcontrol', []) === null, 'C: ahead on bodies — abstains');

// A body the fallback HOLDS (scored <= 0) is never this rule's play: guarded by bot_wipeaware_test F (a Pre Vizsla held for a shown
// wipe — this rule played it before the fix).

bot_test_finish();
