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
// force-fam-HMW-predictions/ too (2026-10-01): its decks carry owner labels, and Hemlock's flavour lives in the registry.
// hmw-meta-2026-09/ and ideas/ too (2026-10-21): 'tempo' is DETECTED from the deck (BotTempo.php), so every labelled list is checked.
$fixtureFiles = array_merge(glob('SWUSim/Tests/BotFixtures/ash-meta-2026-09/*.txt') ?: [],
                            glob('SWUSim/Tests/BotFixtures/force-fam-HMW-predictions/*.txt') ?: [],
                            glob('SWUSim/Tests/BotFixtures/hmw-meta-2026-09/*.txt') ?: [],
                            glob('SWUSim/Tests/BotFixtures/ideas/*.txt') ?: []);
$check(count(glob('SWUSim/Tests/BotFixtures/ideas/*.txt') ?: []) > 0, 'the ideas dir is present');
$check(count(glob('SWUSim/Tests/BotFixtures/force-fam-HMW-predictions/*.txt') ?: []) > 0, 'the HMW predictions dir is present (an empty glob would pass vacuously)');
foreach ($fixtureFiles as $f) {
    $lines = file($f, FILE_IGNORE_NEW_LINES); $sec = ''; $leader = ''; $base = ''; $want = []; $cards = [];
    foreach ($lines as $l) {
        if (preg_match('/^# Flavours:\s*(.*)$/', $l, $m)) $want = array_values(array_filter(array_map('trim', explode(',', $m[1])), 'strlen'));
        if (in_array($l, ['Leader', 'Base', 'Deck', 'Sideboard'], true)) { $sec = $l; continue; }
        if (preg_match('/^(\d+)\s+(\S+)/', $l, $m)) {
            if ($sec === 'Leader' && $leader === '') $leader = $m[2];
            if ($sec === 'Base' && $base === '') $base = $m[2];
            if ($sec === 'Deck') $cards[$m[2]] = ($cards[$m[2]] ?? 0) + intval($m[1]);
        }
    }
    // 'tempo' comes from the deck's own cards (main Deck section), not the registry — checked on its own.
    $check(in_array('tempo', $want, true) === SWUBotIsTempoDeck($leader, $cards), basename($f) . ': header tempo '
        . json_encode(in_array('tempo', $want, true)) . ' = detection (' . round(100 * SWUBotTempoScore($leader, $cards)) . '%)');
    $want = array_values(array_diff($want, ['tempo']));
    SWUBotTempoCacheReset();
    $build(function ($b) use ($leader, $base) { $b->MyLeader($leader, false); $b->MyBase($base); });
    // Feature 'bobaspace' (p43, 2026-10-12) adds 'space' to every Boba Fett (JTL_009) deck on top of the registry; the headers record
    // the registry, so it is switched off here (its own guard: bot_bobaspace_test).
    SWUBotSetDisabledFeatures(['bobaspace']);
    $got = array_values(array_diff(SWUBotDeckFlavours(1), ['tempo']));
    $check($got === $want, basename($f) . ': registry ' . json_encode($got) . ' = header ' . json_encode($want));
    SWUBotSetDisabledFeatures([]);
}

// Lando's Credits are a LANDO plan (owner, 2026-10-20), not a tempo rule: neither Lando deck banks; Krennic (credit-ramp) does.
SWUBotTempoCacheReset();
$build(function ($b) { $b->MyLeader('LAW_018', false); $b->MyBase('JTL_031'); });
$check(!SWUBotBanksCredits(1), 'Lando (Lake Country) does not bank Credits');
$build(function ($b) { $b->MyLeader('LAW_018', false); $b->MyBase('SOR_029'); });
$check(!SWUBotBanksCredits(1), 'Lando (Blue) does not bank Credits');
$build(function ($b) { $b->MyLeader('LAW_008', false); $b->MyBase('SOR_029'); });
$check(SWUBotBanksCredits(1), 'Krennic banks Credits');

bot_test_finish();
