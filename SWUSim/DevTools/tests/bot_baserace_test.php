<?php
// Phase 1b part 3, Task 7 — Control races like Aggro when it is ahead (feature 'baserace'; owner ruling 2026-09-14,
// "B: race like Aggro when ahead"). Control keeps attacking units unless SWUBotIsRacing (Normal's clock test) says it
// wins the race; then it takes Aggro's targets.
// Fixtures: LOF_084 Knight of Ren 4/4 · SOR_095 Battlefield Marine 3/3 · base SOR_020 30 HP.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_baserace_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$check(SWUBotVariantDisabled('no-baserace') === ['baserace'], 'baserace is switchable');

// Racing: 8 left / 4 → 2 rounds, their clock 30 / 3 → 10.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->TheirBase('SOR_020', 22); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check(SWUBotIsRacing(1, 2) === true, 'fixture: seat 1 is racing');
$check($ids(SWUBotStyleFilter($botCtx('control'))) === ['theirBase-0'], 'Control racing: the base (Aggro\'s rule)');
SWUBotSetDisabledFeatures(['baserace']);
$check($ids(SWUBotStyleFilter($botCtx('control'))) === ['theirGroundArena-0'], '@no-baserace: the unit, as before');
SWUBotSetDisabledFeatures([]);

// Not racing (30 / 4 → 8 rounds): Control's rule is unchanged.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_084', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$check(SWUBotIsRacing(1, 2) === false, 'fixture: seat 1 is not racing');
$check($ids(SWUBotStyleFilter($botCtx('control'))) === ['theirGroundArena-0'], 'Control not racing: the unit only');

bot_test_finish();
