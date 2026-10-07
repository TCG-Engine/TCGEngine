<?php
// The wall and the blocker are per ARENA (proposal 'wallarena', 2026-10-07 gap screen).
// FOUND 2026-10-07 diagnosing Darth Vader (JTL) Yellow vs control (bot Vader beats Lando Blue 59% / Aurra Sing DV 74% / Mando Colossus
// 85%; real 25 / 37.5 / 40.3%; .claude/tmp/diag_vader). Vader is SPACE-only (0.0-0.1 ground units through R4), and every Sentinel these
// decks can cast in R1-4 is a GROUND unit. 'wallfirst' (p39) plays the first Sentinel "in an arena" before anything else — any arena,
// whether or not the opponent is there (it fired in 72/85 Mando, 63/74 Aurra, 25/59 Lando losses); 'blockerfirst' (p37) counts "behind
// on units" over BOTH arenas and then plays the best body in EITHER. In R2-R5 rounds where Vader had no ground unit, 2+ ships and a space
// answer was affordable, the bots played a ground unit and no answer in 117/192 (Mando), 81/127 (Aurra), 46/69 (Lando) rounds.
// Aurra s012 R4: hand Eager Escort Fighter, Pirate Snub Fighter, Out the Airlock, Imperial Armored Commando vs four Vader ships —
// "P1 played Imperial Armored Commando"; next round Clone Combat Squadron hit the base for 9 and Aurra died.
//   wallfirst — a Sentinel counts only for an arena the opponent has units in, or against a deck that is not space-flavoured.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wallarena_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(in_array('wallarena', SWUBotProposalList(), true) && SWUBotVariantDisabled('try-wallarena') === ['try:wallarena'], 'wallarena is a switchable proposal');
$first = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $GLOBALS['SWUBotLastRule'] = '';   // else a rule name left over from the previous pick is read
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return [strval($p['cardID'] ?? 'null'), strval($GLOBALS['SWUBotLastRule'] ?? '')];
};
$handCard = function (string $mz) { $o = GetZoneObject(str_replace('!FSM!', '', $mz)); return $o === null ? '?' : strval($o->CardID); };
// Aurra Sing (LAW_004, Data Vault JTL_024), round 4, 5 resources, against Vader (JTL_006 on ASH_026) with four ships and no ground unit.
$vader = function ($b, bool $ground = false) { $b->TheirLeader('JTL_006', false); $b->TheirBase('ASH_026');
    foreach (['JTL_085', 'LAW_135', 'JTL_217', 'JTL_T01'] as $s) $b->WithSpaceUnitForPlayer(2, $s, true);
    if ($ground) $b->WithGroundUnitForPlayer(2, 'SOR_095', true); };
$aurra = function (array $hand, callable $mine, bool $vaderGround = false) use ($build, $vader) {
    $build(function ($b) use ($hand, $mine, $vader, $vaderGround) {
        $b->MyLeader('LAW_004', false); $b->MyBase('JTL_024'); $b->WithCurrentRoundBeing(4); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $mine($b); $vader($b, $vaderGround);
    });
};

// A) wallfirst — Aurra s012 R4: no unit of mine, Commando + two fighters + Out the Airlock in hand.
$A = ['JTL_112', 'LAW_135', 'JTL_079', 'ASH_048'];
$aurra($A, fn($b) => null);
[$p, $rule] = $first();
$check($handCard($p) === 'ASH_048' && $rule === 'wall-first', 'A premise: today wall-first plays the ground Commando into four ships; got ' . $handCard($p) . " ($rule)");
$aurra($A, fn($b) => null);
[$p, $rule] = $first('try-wallarena');
$check($handCard($p) !== 'ASH_048', 'A @try-wallarena: the ground Commando does not go first against an all-space board; got ' . $handCard($p) . " ($rule)");
// …but with a Vader ground unit out, the ground wall is a wall again.
$aurra($A, fn($b) => null, true);
[$p, $rule] = $first('try-wallarena');
$check($handCard($p) === 'ASH_048' && $rule === 'wall-first', 'A2 @try-wallarena: an enemy ground unit makes the ground Sentinel a wall; got ' . $handCard($p) . " ($rule)");

// A3) Against an aggro deck that is NOT space-flavoured (Ahsoka Blue, ASH_009 on a Vigilance base: 'ground'), with no unit out yet, the
// wall still goes first — the lever is about walls a space deck flies over.
$build(function ($b) use ($A) { $b->MyLeader('LAW_004', false); $b->MyBase('JTL_024'); $b->WithCurrentRoundBeing(2); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    foreach ($A as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->TheirLeader('ASH_009', false); $b->TheirBase('ASH_019'); });
$check(!in_array('space', SWUBotDeckFlavours(2), true), 'A3 premise: Ahsoka Blue is not space-flavoured');
[$p, $rule] = $first('try-wallarena');
$check($handCard($p) === 'ASH_048' && $rule === 'wall-first', 'A3 @try-wallarena: vs a ground aggro deck the wall still goes first; got ' . $handCard($p) . " ($rule)");

// (blockerfirst is NOT part of this lever: on the same board with a ground body and a ship in hand, blocker-first already
// puts the SHIP down — Moff Gideon vs Eager Escort or Pirate Snub Fighter — so the cross-arena blocker play did not reproduce.)

bot_test_finish();
