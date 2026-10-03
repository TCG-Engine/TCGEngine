<?php
// Part 20 'readyhost' — an upgrade that attacks with its host when played goes on a READY host. Owner ruling 2026-10-02
// (Ninin's Ahsoka Yellow): JTL_203 Han Solo "should pilot onto a ready ship". Han Solo, Has His Moments — "Piloting /
// When played as an upgrade: You may attack with attached unit." The host scorer added only W['ready'] (0.3) for a ready
// host, so a bigger EXHAUSTED ship won and the attack was thrown away (probe: Han on an exhausted T-6 Shuttle over a ready
// Mando's N-1). The same card tag ('grants-attack') covers SOR_215 / SHD_223 Snapshot Reflexes and SHD_174 Hotshot DL-44.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_readyhost_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-readyhost') === ['readyhost'], 'readyhost is switchable');
$check(in_array('grants-attack', SWUBotCardTags('JTL_203'), true), 'fixture: Han Solo (JTL_203) carries grants-attack');

// Plays hand card 0 and lets the bot answer every prompt. Returns [the host prompt's pick as a CardID, base damage dealt].
$playUpgrade = function (callable $board, string $hostTip, string $variant = '') use (&$gameName, $act, $build) {
    $build($board);
    $base0 = SWUBaseRemainingHp(2);
    $host = null;
    $act(1, 10002, 'myHand-0!FSM!');
    for ($i = 0; $i < 10; $i++) {
        $legal = SWUBotLegalActions($gameName, 1);
        if (($legal['kind'] ?? '') !== 'decision') break;
        $p = SWUBotHeuristicChoose('hyperaggro', (array)$legal['actions'], $legal, $variant);
        $id = strval($p['cardID'] ?? 'PASS');
        if (strval($legal['decisionTooltip'] ?? '') === $hostTip) {
            global $playerID; $saved = $playerID; $playerID = 1; $o = GetZoneObject($id); $playerID = $saved;
            $host = $o ? strval($o->CardID) : $id;
        }
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return [$host, $base0 - SWUBaseRemainingHp(2)];
};

// A) The probe board: an exhausted T-6 Shuttle 1974 (2/6) and a ready Mando's N-1 Starfighter (1/3). Han goes on the N-1
// and it attacks the base with him aboard.
$han = function ($b) { $b->MyLeader('ASH_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'JTL_203');
    $b->WithSpaceUnitForPlayer(1, 'ASH_109', false); $b->WithSpaceUnitForPlayer(1, 'ASH_203', true); $b->FillResourcesForPlayer(2, 'SOR_095', 3); };
[$host, $dmg] = $playUpgrade($han, 'Choose_a_Vehicle_to_pilot');
$check($host === 'ASH_203', "A: Han pilots the READY N-1, not the exhausted T-6; got $host");
// The floor is the N-1's power plus Han's upgrade power; its own On Attack ("exhaust a friendly leader: +2/+0") may add 2.
$floor = intval(CardPower('ASH_203')) + intval(CardUpgradePower('JTL_203'));
$check($dmg >= $floor, "A: …and the N-1 attacks with him for at least $floor (its power + Han's); got $dmg");
[$hostOff, $dmgOff] = $playUpgrade($han, 'Choose_a_Vehicle_to_pilot', 'no-readyhost');
$check($hostOff === 'ASH_109' && $dmgOff === 0, "A @no-readyhost: the exhausted T-6 and no attack (the reported line); got $hostOff / $dmgOff");

// B) Every candidate exhausted: no attack either way, so the ordinary choice stands (the bigger body).
$hanTired = function ($b) { $b->MyLeader('ASH_009'); $b->FillResourcesForPlayer(1, 'SOR_095', 6); $b->WithCardInHandForPlayer(1, 'JTL_203');
    $b->WithSpaceUnitForPlayer(1, 'ASH_109', false); $b->WithSpaceUnitForPlayer(1, 'ASH_203', false); $b->FillResourcesForPlayer(2, 'SOR_095', 3); };
[$h1] = $playUpgrade($hanTired, 'Choose_a_Vehicle_to_pilot');
[$h2] = $playUpgrade($hanTired, 'Choose_a_Vehicle_to_pilot', 'no-readyhost');
$check($h1 === $h2, "B: with no ready host the pick is unchanged; got $h1 vs $h2");
