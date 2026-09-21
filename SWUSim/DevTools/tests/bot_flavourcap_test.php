<?php
// PROPOSAL 'flavourcap' — the flavour rank shift applies only below the control wing (SWUSim/Custom/BotArchetypes.php).
// The 'tempo' flavour shifts a deck one rank toward control. That is the owner's 2026-09-17 ruling for a tempo
// MIDRANGE deck; on a deck ALREADY labelled control it double-counts (Lando Blue: softcontrol + tempo -> hard control).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_flavourcap_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$check(SWUBotVariantDisabled('try-flavourcap') === ['try:flavourcap'], 'flavourcap is a registered proposal');
$GLOBALS['SWUBotTestForceRacing'] = false;   // isolate the flavour shift from the racing shift
$rank = function (string $leader, string $style, bool $cap) use ($build) {
    $build(function ($b) use ($leader) { $b->MyLeader($leader, false, false, true); });
    SWUBotSetDisabledFeatures($cap ? ['try:flavourcap'] : []);
    $r = SWUBotRacingRank($style, 1);
    SWUBotSetDisabledFeatures([]);
    return $r;
};
// LAW_018 Lando: flavours credit-ramp + tempo. LOF_002 Talzin: tempo + force. SOR_014 Sabine: no tempo flavour.
$check($rank('LAW_018', 'softcontrol', false) === 4, 'the shipped bot pilots tempo Lando (softcontrol) as HARD control (rank 4)');
$check($rank('LAW_018', 'softcontrol', true) === 3, 'flavourcap: Lando stays soft control (rank 3)');
$check($rank('LOF_002', 'midrange', false) === 3 && $rank('LOF_002', 'midrange', true) === 3,
    'flavourcap leaves the intended case alone: tempo MIDRANGE still shifts to soft control');
$check($rank('SOR_014', 'softcontrol', true) === $rank('SOR_014', 'softcontrol', false), 'no tempo flavour: unaffected');
$check($rank('LAW_018', 'hardcontrol', false) === 4 && $rank('LAW_018', 'hardcontrol', true) === 4, 'hard control stays hard control either way');
unset($GLOBALS['SWUBotTestForceRacing']);
bot_test_finish();
