<?php
// heal-on-enemy-defeat (owner 2026-10-01): Chimaera's "When an enemy unit is defeated: Heal 2 damage from your base" is
// "what makes the card so good", and with Chimaera out Lost and Forgotten heals 5, not 3. Valued PER POINT of life, board-
// scaled, never flat: the engine by the kills it can expect, every other kill while an engine is in play, capped at the
// base's damage + SWU_BOT_HEAL_HEADROOM. Asserted through _SWUBotPlayValue, comparing the weight on vs zeroed.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_healengine_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const CHIMAERA = 'ASH_052';   // engine: heal 2 per enemy defeat; When Played: paired defeat (counts as one kill)
const LAF = 'LAW_133';        // Lost and Forgotten: "Defeat a non-leader unit. If you do, heal 3 damage from your base."
const CONSULAR = 'SOR_046';   // 3/7 vanilla enemy
$check(in_array('heal-on-enemy-defeat', SWUBotCardTags(CHIMAERA), true) && SWUBotHealOnDefeatAmount(CHIMAERA) === 2,
    'fixture: Chimaera is a heal-on-enemy-defeat engine for 2');

$board = function (int $myBaseDamage, array $hand, array $myUnits, array $theirUnits) use ($build) {
    $build(function ($b) use ($myBaseDamage, $hand, $myUnits, $theirUnits) {
        $b->MyLeader('LAW_008'); $b->TheirBase('SOR_020');
        $b->MyBase('JTL_020');
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($myUnits as $c) $b->WithSpaceUnitForPlayer(1, $c, true);
        foreach ($theirUnits as $c) $b->WithGroundUnitForPlayer(2, $c, true);
    });
    GetBase(1)[0]->Damage = $myBaseDamage;   // set on the LIVE base (WithBaseForPlayer is overridden by CommonSetup)
};
$W = SWUBotWeights('midrange', 1); $w = floatval($W['heal-on-enemy-defeat']);
$W0 = $W; $W0['heal-on-enemy-defeat'] = 0.0;
$gain = fn(string $cid) => _SWUBotPlayValue(1, $cid, $W) - _SWUBotPlayValue(1, $cid, $W0);
$check($w > 0.0, 'the weight is live: ' . $w);

// A: base on 10, an enemy to kill, Lost and Forgotten in hand: Chimaera's own kill + L&F's = 2 kills x 2 = 4 life.
$board(10, [CHIMAERA, LAF], [], [CONSULAR]);
$check(intval(GetBase(1)[0]->Damage) === 10, 'fixture: my base is on 10 damage');
$check(abs($gain(CHIMAERA) - $w * 4) < 1e-9, sprintf('Chimaera with L&F in hand: + %.2f (4 life); got %+.2f', $w * 4, $gain(CHIMAERA)));

// B: the CAP — an undamaged base can only use the headroom (4), however many kills are lined up.
$board(0, [CHIMAERA, LAF, LAF, LAF], [], [CONSULAR]);
$check(abs($gain(CHIMAERA) - $w * SWU_BOT_HEAL_HEADROOM) < 1e-9,
    sprintf('undamaged base, 4 kills lined up (8 life): capped at the headroom, + %.2f; got %+.2f', $w * SWU_BOT_HEAL_HEADROOM, $gain(CHIMAERA)));

// C: nothing to kill — no lifegain to expect.
$board(10, [CHIMAERA, LAF], [], []);
$check(abs($gain(CHIMAERA)) < 1e-9, 'no enemy units: Chimaera expects no kills, so no lifegain value');

// D: Chimaera IN PLAY — Lost and Forgotten now also heals 2 (5 in all): + 2 life on top of its own heal.
$board(10, [LAF], [CHIMAERA], [CONSULAR]);
$check(SWUBotHealPerEnemyKill(1) === 2, 'with Chimaera in play I heal 2 per enemy kill');
$check(abs($gain(LAF) - $w * 2) < 1e-9, sprintf('L&F with Chimaera out: + %.2f (2 more life); got %+.2f', $w * 2, $gain(LAF)));

// E: without an engine in play, L&F gets nothing from this weight.
$board(10, [LAF], [], [CONSULAR]);
$check(abs($gain(LAF)) < 1e-9, 'L&F with no engine in play: no extra lifegain value');

bot_test_finish();
