<?php
// Phase 1b part 2, Task 6 — the flavour registry (RL bots spec, Section 5 "Flavour profiles") and key cards.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_flavours_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$build(function ($b) { $b->MyLeader('JTL_005', false); $b->MyBase('LAW_027'); });
$check(SWUBotDeckFlavours(1) === ['capital-ship'], 'Piett → capital-ship');
$check(SWUBotIsKeyCard(1, 'JTL_143') && SWUBotIsKeyCard(1, 'ASH_099'), 'capital-ship: Capital Ships are key cards');
$check(!SWUBotIsKeyCard(1, 'SEC_110'), 'a GNK Power Droid is filler');
$check(SWUBotIsKeyCard(1, 'LAW_044') && SWUBotIsKeyCard(1, 'JTL_043'), 'answers (wipe, removal) are key cards in every deck');
$build(function ($b) { $b->MyLeader('ASH_009', false); $b->MyBase('JTL_019'); });
$check(SWUBotDeckFlavours(1) === ['ground', 'combo'], 'Ahsoka on a Vigilance base → ground, combo');
$build(function ($b) { $b->MyLeader('ASH_009', false); $b->MyBase('SOR_030'); });
$check(SWUBotDeckFlavours(1) === ['mixed-space'], 'Ahsoka on a Cunning base → mixed-space');
$build(function ($b) { $b->MyLeader('SOR_014', false); });
$check(SWUBotDeckFlavours(1) === [] && !SWUBotIsKeyCard(1, 'ASH_099'), 'an unlabelled leader: no flavours; a Capital Ship is not key');
$check(SWUBotIsKeyCard(1, 'JTL_240'), 'burn cards are key in every deck (tags v2)');

// The fixtures' own headers agree with the registry.
foreach (glob('SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') as $f) {
    $lines = file($f, FILE_IGNORE_NEW_LINES); $sec = ''; $leader = ''; $base = ''; $want = [];
    foreach ($lines as $l) {
        if (preg_match('/^# Flavours:\s*(.*)$/', $l, $m)) $want = array_map('trim', explode(',', $m[1]));
        if ($l === 'Leader' || $l === 'Base') { $sec = $l; continue; }
        if (preg_match('/^\d+\s+(\S+)/', $l, $m)) { if ($sec === 'Leader' && $leader === '') $leader = $m[1]; if ($sec === 'Base' && $base === '') $base = $m[1]; }
    }
    $build(function ($b) use ($leader, $base) { $b->MyLeader($leader, false); $b->MyBase($base); });
    $check(SWUBotDeckFlavours(1) === $want, basename($f) . ': registry ' . json_encode(SWUBotDeckFlavours(1)) . ' = header ' . json_encode($want));
}

bot_test_finish();
