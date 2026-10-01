<?php
// Game log: a non-interactive "when an enemy unit is defeated" observer that resolves INLINE names ITSELF, not the card
// that did the defeating. FOUND 2026-10-01 in the bot baseline logs (~650 lines): P1's ASH_052 Chimaera healed P1's
// base, and the log read "P2's [[LAW_044|Single Reactor Ignition]] healed 2 damage from P1's base" — the stored log
// source still named the defeating card. Fixed with SWULogInlineSource (log-only: game logic keeps reading the stored
// source through SWULogStoredSource, so gameplay is unchanged).
// Real play path: P1 plays Lost and Forgotten ("Defeat a non-leader unit. If you do, heal 3 damage from your base")
// on P2's unit with Chimaera (heal 2) and HK-47 (1 damage to its controller's base) in play.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/gamelog_inline_observer_source_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';

const LAF = 'LAW_133';
const CHIMAERA = 'ASH_052';
const HK47 = 'LOF_130';
$build(function ($b) {
    $b->MyLeader('LAW_008'); $b->MyBase('JTL_020', 10); $b->TheirBase('SOR_020');
    $b->FillResourcesForPlayer(1, 'SOR_095', 10);
    $b->WithCardInHandForPlayer(1, LAF);
    $b->WithSpaceUnitForPlayer(1, CHIMAERA, true);
    $b->WithGroundUnitForPlayer(1, HK47, true);
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true);   // their Consular — the only non-leader enemy
});
$lines = fn() => array_map(fn($e) => preg_replace('/^\w+\|\w+\|@[0-9.]+\|/', '', $e), explode('<NL>', strval(GetGameLog())));
$before = count($lines());
$baseBefore = intval(GetBase(1)[0]->Damage); $theirBefore = intval(GetBase(2)[0]->Damage);

$play = null;
foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (str_starts_with(strval($a['cardID'] ?? ''), 'myHand-0')) { $play = $a; break; }
$check($play !== null, 'fixture: Lost and Forgotten is playable');
$act(1, intval($play['mode'] ?? 10002), strval($play['cardID']));
for ($i = 0; $i < 4; $i++) {   // its target prompt: their Consular (my own units are also legal — pick theirs)
    $legal = SWUBotLegalActions($gameName, 1);
    if (($legal['kind'] ?? '') !== 'decision') break;
    $pick = null;
    foreach ((array)$legal['actions'] as $a) if (strval($a['cardID']) === 'theirGroundArena-0') $pick = $a;
    $pick = $pick ?? ((array)$legal['actions'])[0];
    $act(1, intval($pick['mode'] ?? 10001), strval($pick['cardID']));
}
$new = array_slice($lines(), $before);
$check(count(GetUnitsInArena(2, 'Ground')) === 0, 'fixture: their Consular was defeated');
// 10 damage − 3 (L&F) − 2 (Chimaera) = 5; HK-47 hit their base for 1.
$check(intval(GetBase(1)[0]->Damage) === $baseBefore - 5, 'gameplay: my base healed 3 + 2 (now ' . intval(GetBase(1)[0]->Damage) . ')');
$check(intval(GetBase(2)[0]->Damage) === $theirBefore + 1, 'gameplay: HK-47 dealt 1 to their base');
$has = fn(string $needle) => count(array_filter($new, fn($l) => str_contains($l, $needle))) > 0;
$check($has("P1's [[ASH_052|Chimaera]] healed 2 damage from P1's base"), 'the Chimaera heal names CHIMAERA: ' . json_encode($new));
$check(!$has("[[LAW_133|Lost and Forgotten]] healed 2"), 'and is not credited to Lost and Forgotten');
$check($has("[[LOF_130|HK-47]] dealt 1 damage to P2's base"), 'the HK-47 ping names HK-47');
$check($has("P1's [[LAW_133|Lost and Forgotten]] healed 3 damage from P1's base"), 'Lost and Forgotten\'s OWN heal still names Lost and Forgotten');
// The override is in-request only and gone afterwards; the stored source is what game logic reads.
$check(($GLOBALS['gSWULogSrcOverride'] ?? null) === null, 'the log-only override is cleared after the effect');

bot_test_finish();
