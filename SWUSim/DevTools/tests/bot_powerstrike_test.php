<?php
// power-strike is BOARD-SCALED (owner 2026-10-01), never a flat weight: with no friendly unit to strike with, the
// damage-enemy-unit value the card carries is taken back; when the striker's power can defeat an enemy unit it is worth
// a kill on top. A card whose striker is ITSELF ("This unit deals damage equal to his power") needs no other unit.
// Asserted through _SWUBotPlayValue — what the bot's play scoring calls.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_powerstrike_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$GLOBALS['SWUBotPinnedDisabled'] = SWU_BOT_PART38_FEATURES;   // isolates this file's feature from curve value (p38, 2026-10-06)

const STRIKE_TRUE = 'SOR_127';  // event: "A friendly unit deals damage equal to its power to an enemy unit."
const CROSSHAIR = 'SHD_087';    // unit: "Action: This unit deals damage equal to his power to an enemy ground unit."
const MARINE = 'SOR_095';       // 3/3 vanilla
const CONSULAR = 'SOR_046';     // 3/7 vanilla

$check(in_array('power-strike', SWUBotCardTags(STRIKE_TRUE), true) && in_array('damage-enemy-unit', SWUBotCardTags(STRIKE_TRUE), true),
    'fixture: Strike True carries power-strike + damage-enemy-unit');
$check(in_array('power-strike', SWUBotCardTags(CROSSHAIR), true), 'fixture: Crosshair carries power-strike');

// $mine / $theirs: lists of [cardID, damage] ground units.
$board = function (array $mine, array $theirs) use ($build) {
    $build(function ($b) use ($mine, $theirs) {
        $b->MyLeader('LAW_018'); $b->MyBase('SOR_020'); $b->TheirBase('SOR_020');
        foreach ($mine as [$c, $d]) $b->WithGroundUnitForPlayer(1, $c, true, $d);
        foreach ($theirs as [$c, $d]) $b->WithGroundUnitForPlayer(2, $c, true, $d);
    });
};
$W = SWUBotWeights('midrange', 1);
$W0 = $W; $W0['damage-enemy-unit'] = 0.0;     // the same weights without the card's damage value
$dmg = floatval($W['damage-enemy-unit']); $kill = floatval($W['kill']);

// A: NO friendly unit — no striker. The damage value is taken back, so it is as if damage-enemy-unit were 0.
$board([], [[MARINE, 0]]);
$noStriker = _SWUBotPlayValue(1, STRIKE_TRUE, $W);
$check(abs($noStriker - _SWUBotPlayValue(1, STRIKE_TRUE, $W0)) < 1e-9,
    'no friendly unit: Strike True earns NOTHING for its damage (the damage-enemy-unit value is taken back)');

// B: my 3-power Marine vs an enemy on 3 remaining — the strike kills: + damage + kill.
$board([[MARINE, 0]], [[MARINE, 0]]);
$kills = _SWUBotPlayValue(1, STRIKE_TRUE, $W);
$check(abs(($kills - $noStriker) - ($dmg + $kill)) < 1e-9,
    sprintf('a striker that can KILL: + damage (%.2f) + kill (%.2f) over no striker; got %+.2f', $dmg, $kill, $kills - $noStriker));

// C: my 3-power Marine vs a 7-HP Consular — it hits but cannot kill: + damage only.
$board([[MARINE, 0]], [[CONSULAR, 0]]);
$hits = _SWUBotPlayValue(1, STRIKE_TRUE, $W);
$check(abs(($hits - $noStriker) - $dmg) < 1e-9,
    sprintf('a striker that CANNOT kill: + damage only (%.2f); got %+.2f', $dmg, $hits - $noStriker));

// D: Crosshair strikes with ITSELF — no other friendly unit needed, so its damage value is NOT taken back.
$board([], [[CONSULAR, 0]]);
$check(SWUBotPowerStrikePower(1, CROSSHAIR) === intval(CardPower(CROSSHAIR)) && intval(CardPower(CROSSHAIR)) > 0,
    'Crosshair strikes with its OWN printed power on an empty friendly board: ' . SWUBotPowerStrikePower(1, CROSSHAIR));
$check(_SWUBotPlayValue(1, CROSSHAIR, $W) - _SWUBotPlayValue(1, CROSSHAIR, $W0) > 0,
    'Crosshair keeps its damage value with no other friendly unit (the self-striker is not "no striker")');

bot_test_finish();
