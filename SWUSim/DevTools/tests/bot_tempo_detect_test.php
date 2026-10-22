<?php
// Tempo detection (spec docs/superpowers/specs/2026-10-20-swusim-tempo-flavour-design.md §1) — classification infra, so it is
// unit-tested (bot BEHAVIOUR is not). Pins the owner's piece kinds and NOT-tempo rulings, the score weights and the > 28% rule.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_tempo_detect_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

$kind = fn($id) => SWUBotTempoPieceKind($id);
// Tempo pieces — aimed at the opponent (owner, 2026-10-20).
$check($kind('JTL_217') === 'exhaust', 'Death Space Skirmisher exhausts an enemy');
$check($kind('SEC_037') === 'exhaust', 'Cantwell Arrestor Cruiser exhausts an enemy');
$check($kind('LOF_231') === 'ambush', 'Darth Tyranus gives HIMSELF Ambush');
$check($kind('LOF_200') === 'ambush', 'Qui-Gon Jinn (LOF_200) has Ambush');
$check($kind('LOF_031') === 'debuff', 'Karis -2/-2 is a stat reduction although tagged removal');
$check($kind('LOF_035') === 'debuff', "Talzin's Assassin -3/-3");
$check($kind('SEC_038') === 'debuff', 'Condemn');
$check($kind('SEC_233') === 'bounce', 'Beguile');
$check($kind('LAW_241') === 'bounce', 'The Blade Wing');
$check($kind('ASH_220') === 'handhate', 'Remnant Lookouts');
$check($kind('SEC_186') === 'handhate', 'Garindan');
$check($kind('HMW_202') === 'weakness', 'Inferno Squad');
$check($kind('HMW_062') === 'weakness', 'Nuvo Vindi');
$check($kind('SEC_195') === 'capture', 'Arrest');
$check($kind('HMW_095') === 'lock', "Carbonite Chamber: doesn't ready (text, no tag)");
// NOT tempo (owner rulings).
$check($kind('JTL_043') === null, 'No Glory, Only Results = take control = removal');
$check($kind('ASH_047') === null, 'Gar Saxon');
$check($kind('LOF_111') === null, 'Maz Kanata (LOF) buffs a friendly attack');
$check($kind('HMW_078') === null, 'Qui-Gon Jinn (HMW) is removal');

// Score: cost weight + leader weight.
$check(abs(SWUBotTempoScore('SOR_014', ['JTL_217' => 4]) - 1.25) < 1e-9, 'a cost<=5 piece counts 1.25');
$check(abs(SWUBotTempoScore('SOR_014', ['LAW_241' => 4]) - 1.0) < 1e-9, 'a cost>=6 piece counts 1.0');
$check(abs(SWUBotTempoScore('LOF_002', ['ASH_047' => 50]) - 5 / 50) < 1e-9, 'a tempo leader adds 5');
$check(abs(SWUBotTempoScore('LOF_016', ['ASH_047' => 50])) < 1e-9, 'Qui-Gon (LOF_016) is not a tempo leader');
$check(SWUBotTempoScore('SOR_014', []) === 0.0, 'an empty deck scores 0');
// The rule: tempo iff > 28%.
$check(!SWUBotIsTempoDeck('SOR_014', ['JTL_217' => 11, 'ASH_047' => 39]), '27.5% is not tempo');
$check(SWUBotIsTempoDeck('SOR_014', ['JTL_217' => 12, 'ASH_047' => 38]), '30% is tempo');

// Fixtures the owner reads as tempo / not (main Deck section).
$mainDeck = function (string $path): array {
    $leader = ''; $cards = []; $sec = '';
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $l) {
        if (in_array($l, ['Leader', 'Base', 'Deck', 'Sideboard'], true)) { $sec = $l; continue; }
        if (!preg_match('/^(\d+)\s+(\S+)/', $l, $m)) continue;
        if ($sec === 'Leader' && $leader === '') $leader = $m[2];
        if ($sec === 'Deck') $cards[$m[2]] = ($cards[$m[2]] ?? 0) + intval($m[1]);
    }
    return [$leader, $cards];
};
foreach (['hmw-meta-2026-09/doctor-hemlock_hmw_yellow_naboo' => true, 'ideas/lando-calrissian_law_lake-country' => true,
          'ash-meta-2026-09/director-krennic_law_blue' => false] as $fx => $want) {
    [$leader, $cards] = $mainDeck("SWUSim/Tests/BotFixtures/$fx.txt");
    $check(count($cards) > 0 && SWUBotIsTempoDeck($leader, $cards) === $want, "$fx is " . ($want ? '' : 'not ') . 'tempo ('
        . round(100 * SWUBotTempoScore($leader, $cards)) . '%)');
}

// Live: the bot reads its OWN cards; a fresh board is re-read after the cache reset; Twin Suns is never tempo.
$build(function ($b) { $b->MyLeader('LOF_002', false); for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(1, 'JTL_217'); });
$check(SWUBotSeatIsTempo(1), '20 Death Space Skirmishers + Talzin: tempo');
SWUBotTempoCacheReset();
$build(function ($b) { $b->MyLeader('LOF_002', false); for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(1, 'ASH_047'); });
$check(!SWUBotSeatIsTempo(1), '20 Gar Saxons + Talzin: 5/20 = 25%, not tempo');
SWUBotTempoCacheReset();
$build(function ($b) { $b->MyLeader('SOR_014', false); });
$check(!SWUBotSeatIsTempo(1), 'no cards at all: not tempo, no division by zero');
SWUBotTempoCacheReset();
$build(function ($b) { $b->WithSeatOrder('123'); $b->MyLeader('LOF_002', false); for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(1, 'JTL_217'); });
$check(!SWUBotSeatIsTempo(1), 'Twin Suns (3 seats): never tempo (Premier only)');
SWUBotTempoCacheReset();

bot_test_finish();
