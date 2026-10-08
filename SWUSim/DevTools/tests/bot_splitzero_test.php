<?php
// Feature 'splitzero' (p42) — an "up to N" damage split (MZSPLITASSIGN …|UPTO; JTL_009 Boba Fett deployed as a pilot: "When deployed as an
// upgrade: Deal up to 4 damage divided as you choose among any number of units"). Leader audit 2026-10-08: the bot's split answers for an
// UPTO prompt ran from N down to 1 and never offered assigning NOTHING ('-', a total of 0 — CR "up to"), so with no enemy unit the split
// was forced onto the bot's own units (127 of Boba's splits, 6.6%); and the bridge's enumeration (200 per total, 300 in all) walks the
// targets in prompt order, MY units first — on a wide board the all-enemy splits were cut off (once, "kill all four 1-HP enemies" was not
// on the list). Fixed: '-' is offered, and the all-enemy splits are enumerated first, for every total.
// Fixtures (dictionary-checked): JTL_009 Boba Fett · JTL_020 base · ASH_099 Gozanti (my Vehicle host) · SOR_095 Battlefield Marine (mine)
//   · SOR_225 TIE/ln Fighter (theirs, 2/1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_splitzero_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-splitzero') === ['splitzero'], 'splitzero is switchable');
$check(in_array('splitzero', SWUBotFeatureGroups()['p42'] ?? [], true), 'splitzero is in group p42');
// ⚠ The split ANSWERS are built by the enumerator (SWUBotLegalActions), before the variant reaches the chooser — so a "today" arm must
// switch the feature off around the enumeration too.
$pick = function (string $variant = '') use (&$gameName) {
    SWUBotSetDisabledFeatures($variant === '' ? [] : (array)SWUBotVariantDisabled($variant));
    $l = SWUBotLegalActions($gameName, 1);
    SWUBotSetDisabledFeatures([]);
    $p = SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
    return strval($p['cardID'] ?? '');
};
// Boba (undeployed), 6 resources, my Gozanti (the only Vehicle: the pilot auto-attaches) + $mine Marines; their $ties TIE/ln. Deploy as Pilot.
$split = function (int $mine, int $ties) use ($build, $act, $botCtx) {
    $build(function ($b) use ($mine, $ties) {
        $b->MyLeader('JTL_009', true); $b->MyBase('JTL_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCurrentRoundBeing(6);
        $b->WithSpaceUnitForPlayer(1, 'ASH_099', true);
        for ($k = 0; $k < $mine; $k++) $b->WithGroundUnitForPlayer(1, 'SOR_095', false);
        for ($k = 0; $k < $ties; $k++) $b->WithSpaceUnitForPlayer(2, 'SOR_225', false);
        for ($k = 0; $k < 30; $k++) { $b->WithCardInDeckForPlayer(1, 'SOR_095'); $b->WithCardInDeckForPlayer(2, 'SOR_095'); }
    });
    $act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
    $act(1, 100, 'Pilot');
    return [$botCtx('midrange')['type'], $botCtx('midrange')['param']];
};
$ownHit = fn(string $a) => (bool)preg_match('/(^|,)my[A-Za-z]+-\d+:\d+/', $a);

// A) No enemy unit: today the 4 damage lands on my own units; fixed, nothing is assigned.
[$t, $p] = $split(2, 0);
$check($t === 'MZSPLITASSIGN' && str_ends_with($p, '|UPTO'), 'A fixture: the "up to 4" split is pending; got ' . $t . ' ' . $p);
$check($ownHit($pick('no-splitzero')), 'A fixture: today it hits my own units; got ' . $pick('no-splitzero'));
$check($pick() === '-', 'A: nothing to hit — nothing assigned; got ' . $pick());
$act(1, 100, '-');
$hurt = 0; foreach (array_merge(GetZone('myGroundArena'), GetZone('mySpaceArena')) as $o) $hurt += intval($o->Damage ?? 0);
$check($hurt === 0, 'A: the engine takes "-" — none of my units is damaged; got ' . $hurt);

// B) Four 1-HP TIEs behind six of my Marines (listed first): the "kill all four" split is on the list and taken.
[$t, $p] = $split(6, 4);
$check($t === 'MZSPLITASSIGN', 'B fixture: the split is pending');
$got = $pick();
$kills = preg_match_all('/theirSpaceArena-\d+:1(,|$)/', $got);
$check($kills === 4 && !$ownHit($got), 'B: one point on each TIE — all four die; got ' . $got);

bot_test_finish();
