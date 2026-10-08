<?php
// Features 'aspectpick' + 'actionfirst' (p42) — LAW_018 Lando Calrissian, Full Sabacc: "Action [1 resource, Exhaust]: Choose an aspect,
// then discard a card from a deck. If it has the chosen aspect, create a Credit token." / deployed "When Deployed: You may defeat a
// friendly Credit token. If you do, create 3 Credit tokens."
// Leader audit 2026-10-08: (1) every aspect tied, so the FIRST listed (Vigilance) was named 7,059 of 7,059 times — right for this 100%-Vigilance
// list by luck; (2) 47% of deploys had no Credit, and the Action was never used in the deploy round — though a leader deploys whether
// ready or exhausted, and enters ready (CR 4.329). The audit's cross-cutting gap: Lando 0 of 1,092 deploys after the Action, Obi-Wan
// 0/1,011, Talzin 35/1,965. Fixed: the aspect named is the one most of my remaining deck has (my own list — never its order); and a
// deploy waits behind the SAME leader's front Action while that Action is worth using — except where the deploy discounts what the
// Action plays (JTL_005 Piett: deploy first, then the ships at 2 less).
// Fixtures (dictionary-checked): LAW_018 Lando · SOR_020 base · SOR_095 Battlefield Marine (Command/Heroism) · LAW_118 Droid Laser Turret
//   (Vigilance) · LOF_008 Obi-Wan Kenobi · LOF_020 base · JTL_005 Piett · JTL_038 Corvus (Capital Ship) · JTL_009 Boba Fett (Aggression/Villainy) · JTL_240 (Villainy only).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lando_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
foreach (['aspectpick', 'actionfirst'] as $f) {
    $check(SWUBotVariantDisabled("no-$f") === [$f], "$f is switchable");
    $check(in_array($f, SWUBotFeatureGroups()['p42'] ?? [], true), "$f is in group p42");
}
$pick = function (string $variant = '') use (&$gameName) {
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Lando, $res resources ($epicUsed: no deploy), my deck 20 Marines (Command/Heroism) + 10 Turrets (Vigilance), their Marine.
$board = function (int $res, bool $epicUsed) use ($build) {
    $build(function ($b) use ($res, $epicUsed) {
        $b->MyLeader('LAW_018', true, false, $epicUsed); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', $res); $b->WithCurrentRoundBeing(6);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, $k < 20 ? 'SOR_095' : 'LAW_118'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
};
$ability = 'myLeader-0!CustomInput!LeaderAbility';
$deploy = 'myLeader-0!CustomInput!DeployLeader:Unit';

// A) The aspect: my deck is 20 Command/Heroism to 10 Vigilance — today Vigilance (listed first); fixed, Command or Heroism.
$board(4, false);   // pre-flip (4 resources: no deploy yet)
$act(1, 10001, $ability);
$check($botCtx('midrange')['tooltip'] === 'Choose_an_aspect', 'A fixture: the aspect pick is pending; got ' . $botCtx('midrange')['tooltip']);
// (Run without 'landomill', shipped 2026-10-08, whose own pre-flip pick reads the same deck count — this case is 'aspectpick' alone.)
$check(in_array($pick('no-landomill'), ['Command', 'Heroism'], true), 'A: the deck\'s most common aspect; got ' . $pick('no-landomill'));

// A2) The real Lando Blue list (every card is Vigilance): Vigilance — owner 2026-10-08, "Lando blue should always pick Vigilance".
$build(function ($b) {
    $b->MyLeader('LAW_018', true, false, false); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(4);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    $list = file('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/lando-calrissian_law_blue.txt', FILE_IGNORE_NEW_LINES); $in = false;
    foreach ($list as $line) {
        if (trim($line) === 'Deck') { $in = true; continue; }
        if (trim($line) === 'Sideboard') break;
        if ($in && preg_match('/^(\d+) (\S+)/', $line, $m)) for ($k = 0; $k < intval($m[1]); $k++) $b->WithCardInDeckForPlayer(1, $m[2]);
    }
    for ($k = 0; $k < 30; $k++) $b->WithCardInDeckForPlayer(2, 'SOR_095');
});
$act(1, 10001, $ability);
$check($pick() === 'Vigilance', 'A2: the real Lando Blue list — Vigilance; got ' . $pick());

// A3) Milling THEIR deck after the flip ('landomill', the owner's 2026-09-23 line): vs Boba Fett JTL (Aggression/Villainy), name his color —
// Aggression — not Villainy (owner 2026-10-08: "against a Lake Country deck, it is safe to pick the aspect that is not Hero or Villain").
// Their shown cards are Villainy-only here, which the shown-cards count alone would follow.
$build(function ($b) {
    $b->MyLeader('LAW_018', true, false, true); $b->MyBase('SOR_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithCurrentRoundBeing(7);
    $b->TheirLeader('JTL_009', true); $b->WithGroundUnitForPlayer(2, 'JTL_240', false); $b->WithCardInDiscardForPlayer(2, 'JTL_240');
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$act(1, 10001, $ability);
$check($pick('try-landomill') === 'Aggression', 'A3: milling Boba\'s deck — his color, Aggression; got ' . $pick('try-landomill'));

// B) The generic rule, on LOF_008 Obi-Wan (audit: 0 of 1,011 deploys after his Action): deploy round (5 resources), the Force, my Marine —
// his Experience first, then the deploy. Today the deploy. (Lando's own flip timing is 'landoflip' — bot_landoflip_test.)
$build(function ($b) {
    $b->MyLeader('LOF_008', true); $b->MyBase('LOF_020'); $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithCurrentRoundBeing(5); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick('no-actionfirst') === $deploy, 'B fixture: today Obi-Wan deploys first; got ' . $pick('no-actionfirst'));
$check($pick() === $ability, 'B: the Action before the deploy; got ' . $pick());

// C) Piett is the exception: his deploy makes the Action's ship cheaper — deploy first, as today.
$build(function ($b) {
    $b->MyLeader('JTL_005', true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
    $b->WithCardInHandForPlayer(1, 'JTL_038'); $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
});
$check($pick() === $deploy, 'C: Piett deploys before his ship Action; got ' . $pick());

bot_test_finish();
