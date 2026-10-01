<?php
// debuff-all-enemy-units (owner 2026-10-01): "give each enemy unit -X/-X" is worth its weight once PER ENEMY UNIT the
// shrink would kill — "scales in value the more weak units there are on their side". The three cards: SEC_051
// Bo-Katan Kryze (-3/-3), TWI_075 Disruptive Burst (-1/-1), LAW_101 Lawbringer (-2/-2 to ONE chosen aspect).
// Asserted through _SWUBotPlayValue — the function the bot's play scoring actually calls — not only the counter.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_debuffall_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const MARINE = 'SOR_095';   // Battlefield Marine 3/3 — Command, Heroism (vanilla)
const TROOPER = 'SOR_128';  // Death Star Stormtrooper 3/1 — Aggression, Villainy (vanilla)
const CONSULAR = 'SOR_046'; // Consular Security Force 3/7 — Vigilance, Heroism (vanilla)

// Enemy board (seat 2), by REMAINING HP: Marine 3, Marine 2, Marine 1, Trooper 1, Consular 7.
// My board (seat 1): a Marine on 1 remaining — it must never count.
$board = function (bool $enemies) use ($build) {
    $build(function ($b) use ($enemies) {
        $b->MyLeader('LAW_018'); $b->MyBase('SOR_020'); $b->TheirBase('SOR_020');
        $b->WithGroundUnitForPlayer(1, MARINE, true, 2);
        if ($enemies) {
            $b->WithGroundUnitForPlayer(2, MARINE, true, 0);
            $b->WithGroundUnitForPlayer(2, MARINE, true, 1);
            $b->WithGroundUnitForPlayer(2, MARINE, true, 2);
            $b->WithGroundUnitForPlayer(2, TROOPER, true, 0);
            $b->WithGroundUnitForPlayer(2, CONSULAR, true, 0);
        }
    });
};

$board(true);
$check(SWUBotDebuffAllEnemyKills(1, 'SEC_051') === 4,
    'Bo-Katan -3/-3 kills every enemy on 3 or less remaining (Marines on 3/2/1 + the 1-HP Trooper) = 4, not the 7-HP Consular: got ' . SWUBotDebuffAllEnemyKills(1, 'SEC_051'));
$check(SWUBotDebuffAllEnemyKills(1, 'TWI_075') === 2,
    'Disruptive Burst -1/-1 kills only the two 1-remaining enemies: got ' . SWUBotDebuffAllEnemyKills(1, 'TWI_075'));
// Lawbringer: -2/-2 to ONE aspect. Killable (<=2): Marine(2), Marine(1) [Command,Heroism] and Trooper(1) [Aggression,
// Villainy]. Best aspect = Command (or Heroism) with 2 — not the 3 an all-units count would say.
$check(SWUBotDebuffAllEnemyKills(1, 'LAW_101') === 2,
    'Lawbringer counts the BEST single aspect (2), not every weak unit (3): got ' . SWUBotDebuffAllEnemyKills(1, 'LAW_101'));
$check(SWUBotDebuffAllEnemyKills(2, 'TWI_075') === 1,
    'from the OTHER seat, only MY weak Marine is an enemy — my own units never count against me: got ' . SWUBotDebuffAllEnemyKills(2, 'TWI_075'));

// The play value: exactly W x kills more than the same card on an empty enemy board, and nothing flat.
$W = SWUBotWeights('midrange', 1);
$withEnemies = _SWUBotPlayValue(1, 'SEC_051', $W);
$board(false);
$check(SWUBotDebuffAllEnemyKills(1, 'SEC_051') === 0, 'no enemy units -> zero kills');
$empty = _SWUBotPlayValue(1, 'SEC_051', $W);
$want = 4 * floatval($W['debuff-all-enemy-units']);
$check(abs(($withEnemies - $empty) - $want) < 1e-9,
    sprintf('Bo-Katan is worth W x 4 kills (%.2f) more on that board than on an empty one: got %.2f', $want, $withEnemies - $empty));
$check(floatval($W['debuff-all-enemy-units']) > 0, 'the weight is non-zero, so the scaling is live (not an inert tag)');
// ...and NOTHING flat: on the empty board, zeroing the weight must not change the value. (A difference-only check
// cannot see a flat term — it cancels between the two boards; a mutant that ALSO added the weight flat got through.)
$W0 = $W; $W0['debuff-all-enemy-units'] = 0.0;
$check(abs(_SWUBotPlayValue(1, 'SEC_051', $W0) - $empty) < 1e-9,
    'no flat term: with no killable enemy, the tag adds exactly 0 — it is never summed as a flat weight');

bot_test_finish();
