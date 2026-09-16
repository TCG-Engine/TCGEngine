<?php
// Feature 'wipekeep' — CONTROL KEEPS THE WIPE IT WILL NEED. Owner ruling 2026-09-16:
//   "on the 2R turn to the 5R turn, keep the cheapest wipe that is relevant to the opponent (HSD for space, SRI for
//    mixed or ground aggro). for 6R up, gauge whether it's needed. if you stabilized enough to not need it, then
//    resource it if it will also hurt your own board. however, if it only affects their arena for example, HSD,
//    then keep it."
//
// Found by the 2026-09-16 fidelity baseline (23 decks, 7,590 games). Aggro ran +8.1 points hot and control -11.6
// cold; the worst case was Vader Yellow beating Lando 76.7% in bot-land against 6.7% in tournaments. Every control
// deck's answer to a space board costs 7-9, and control cast Hyperspace Disaster in only 23 of 120 Vader games
// (won 47.8% when it did, 7.2% when it did not — survivorship-biased, but the direction is clear). Lando draws HSD
// in ~69% of games by round 6 yet cast it in 27%. A probe found why: control keeps "castable within ~2 regroups +
// ONE bomb" (owner rule 2026-09-13). Bo-Katan (9) takes the bomb slot, HSD (7) sits just past the 6-resource
// horizon at 3 resources, and its key-card bonus (+50) lifts it only to 49 — below every castable card — so it is
// the FIRST card resourced.
//
// ⚠ "Only affects their arena" is judged against MY ACTUAL BOARD, not a static label. Hyperspace Disaster reads
// "Defeat all space units" — both sides. It is costless to me exactly when I control no space unit, which is the
// usual case for these ground-based control decks and is what the owner's example assumes.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wipekeep_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

const HSD = 'SEC_078';   // Hyperspace Disaster, 7 — "Defeat all space units."
const SRI = 'LAW_044';   // Single Reactor Ignition, 8 — "Defeat all units."
const BOMB = 'SEC_051';  // Bo-Katan Kryze, 9 — takes the single bomb slot in every hand, so no wipe is the bomb
const OWEN = 'LOF_057';  // 1-cost filler
const BARRISS = 'ASH_044'; // 3-cost filler

$check(SWUBotVariantDisabled('no-wipekeep') === ['wipekeep'] && in_array('wipekeep', (array)SWUBotVariantDisabled('no-p3'), true),
    'wipekeep is switchable, alone and in the part-3 group');

// The first $n cards the control resourcer would put into resources, as card IDs.
$resourced = function (int $n, array $disabled) use ($botCtx) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx('control');
    $out = [];
    foreach (SWUBotChooseResourceCards($ctx, $n) as $mz) {
        $o = GetHand(1)[intval(substr($mz, strlen('myHand-')))] ?? null;
        $out[] = strval($o->CardID ?? '?');
    }
    SWUBotSetDisabledFeatures([]);
    return $out;
};
// $opp: list of [arena, cardID] enemy units. $mine: list of [arena, cardID] friendly units.
$board = function (int $res, array $hand, array $opp = [], array $mine = [], int $myBaseDamage = 0) use ($build) {
    $build(function ($b) use ($res, $hand, $opp, $mine, $myBaseDamage) {
        $b->MyLeader('LAW_018');
        $b->MyBase('SOR_020', $myBaseDamage);
        $b->TheirBase('SOR_020', 0);
        $b->FillResourcesForPlayer(1, 'SOR_095', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        foreach ($opp as [$arena, $c]) $arena === 'Space' ? $b->WithSpaceUnitForPlayer(2, $c, true) : $b->WithGroundUnitForPlayer(2, $c, true);
        foreach ($mine as [$arena, $c]) $arena === 'Space' ? $b->WithSpaceUnitForPlayer(1, $c, true) : $b->WithGroundUnitForPlayer(1, $c, true);
    });
};
$SPACE = ['Space', 'JTL_095'];   // an enemy A-Wing
$GROUND = ['Ground', 'SOR_095']; // an enemy ground unit

// ── 2R-5R: keep the cheapest RELEVANT wipe ──────────────────────────────────────────────────────

// The reported defect: 3 resources, a space opponent, HSD + the bomb. HSD must survive.
$board(3, [HSD, BOMB, OWEN, BARRISS, 'JTL_078'], [$SPACE]);
$old = $resourced(1, ['wipekeep']);
$new = $resourced(1, []);
$check($old[0] === HSD, 'the old model resourced Hyperspace Disaster first vs a space deck; got ' . $old[0]);
$check($new[0] !== HSD, 'against a space deck, HSD is kept on the 3R turn; resourced ' . $new[0]);

// Pure ground opponent: HSD cannot touch their board, so it is not the relevant wipe — SRI is.
$hand = [BOMB, HSD, SRI, OWEN, BARRISS];
$board(3, $hand, [$GROUND]);
$old = $resourced(1, ['wipekeep']);
$new = $resourced(1, []);
$check($old[0] === SRI, 'the old model resourced SRI first (furthest from castable); got ' . $old[0]);
$check($new[0] === HSD, 'vs ground aggro, SRI is the kept wipe and the irrelevant HSD goes first; resourced ' . $new[0]);

// Mixed opponent: HSD leaves their ground half standing, so again SRI is the relevant wipe.
$board(3, $hand, [$SPACE, $GROUND]);
$new = $resourced(1, []);
$check($new[0] === HSD, 'vs a mixed board, SRI is kept over HSD; resourced ' . $new[0]);

// WHICH wipe is protected, asserted directly. ⚠ Not via "the first two cards resourced": the resourcer's horizon
// is resources + n + 2, so asking for two picks widens it to 7 and pulls HSD inside it by itself, which is not the
// behaviour under test (a real regroup resources one card). And with n = 1 both models resource SRI first against a
// space board — SRI is the dearer and further card — so the difference only shows on the NEXT regroup.
$protected = function () { return _SWUBotProtectedWipe(1, 'control'); };
$board(3, $hand, [$SPACE]);
$check($protected() === HSD, 'vs space both wipes cover their board, so the CHEAPEST relevant one (HSD) is protected; got ' . var_export($protected(), true));
$board(3, $hand, [$GROUND]);
$check($protected() === SRI, 'vs ground, only SRI covers their board; got ' . var_export($protected(), true));
$board(3, $hand, [$SPACE, $GROUND]);
$check($protected() === SRI, 'vs a mixed board, only SRI covers both arenas; got ' . var_export($protected(), true));
$board(3, [BOMB, SRI, OWEN, BARRISS], [$SPACE]);
$check($protected() === SRI, 'vs space with no HSD in hand, SRI is the cheapest relevant wipe left; got ' . var_export($protected(), true));
$board(3, [BOMB, HSD, OWEN, BARRISS], [$GROUND]);
$check($protected() === null, 'vs ground with only HSD in hand, no wipe is relevant, so none is protected; got ' . var_export($protected(), true));
$board(3, [BOMB, HSD, SRI, OWEN], []);
$check($protected() === HSD, 'with no enemy units yet the need is unknown: keep the cheapest wipe rather than throw an answer away; got ' . var_export($protected(), true));

// ── 6R+: gauge whether it is still needed ────────────────────────────────────────────────────────

// Stabilized, and SRI would also wipe MY board → it may go to resources.
$board(6, [BOMB, SRI, OWEN, BARRISS], [$GROUND], [['Ground', 'SOR_095'], ['Ground', 'SOR_095']]);
$old = $resourced(1, ['wipekeep']);
$new = $resourced(1, []);
$check($old[0] !== SRI, 'the old model kept SRI at 6R (castable soon); resourced ' . $old[0]);
$check($new[0] === SRI, 'stabilized at 6R, SRI would hurt my own board, so it is resourced; resourced ' . $new[0]);

// Same hand, NOT stabilized (my base is nearly dead) → still needed, so SRI stays.
$board(6, [BOMB, SRI, OWEN, BARRISS], [$GROUND, $GROUND, $GROUND], [['Ground', 'SOR_095'], ['Ground', 'SOR_095']], 27);
$new = $resourced(1, []);
$check($new[0] !== SRI, 'under pressure at 6R, SRI is still needed and kept; resourced ' . $new[0]);

// Stabilized, but HSD only touches THEIR arena (I control no space unit) → keep it.
$board(6, [BOMB, HSD, OWEN, BARRISS], [$SPACE], [['Ground', 'SOR_095'], ['Ground', 'SOR_095']]);
$new = $resourced(1, []);
$check($new[0] !== HSD, 'stabilized at 6R, HSD hits only their arena, so it is kept; resourced ' . $new[0]);

// Stabilized, and I DO control a space unit → HSD now hurts my board too, so it may go.
$board(6, [BOMB, HSD, OWEN, BARRISS], [$SPACE], [['Space', 'JTL_095'], ['Ground', 'SOR_095']]);
$new = $resourced(1, []);
$check($new[0] === HSD, 'stabilized at 6R with my own space unit, HSD would hurt me, so it is resourced; resourced ' . $new[0]);

// Owner refinement 2026-09-16: "if i have one or two weenie space units and they have a swarm, then i would still
// keep it and use it." Touching my board is not enough to release a wipe — it goes only when the trade is NOT
// clearly in my favour. Paired with the check above, where one A-Wing each is an even trade and HSD is released.
$board(6, [BOMB, HSD, OWEN, BARRISS], [$SPACE, $SPACE, $SPACE], [['Space', 'JTL_158'], ['Ground', 'SOR_095']]);
$new = $resourced(1, []);
$check($new[0] !== HSD, 'stabilized at 6R, one weenie of mine vs their swarm of three: HSD is still kept; resourced ' . $new[0]);
// ...and the same test for the all-units wipe: my one cheap body against their swarm keeps SRI too.
$board(6, [BOMB, SRI, OWEN, BARRISS], [$GROUND, $GROUND, $GROUND], [['Ground', 'LOF_057']]);
$new = $resourced(1, []);
$check($new[0] !== SRI, 'stabilized at 6R, one weenie of mine vs their ground swarm: SRI is still kept; resourced ' . $new[0]);

// A ONE-SIDED wipe never costs me by its wording, however big my board. Pre Vizsla (ASH_053) is tagged a wipe and
// reads "Defeat any number of non-leader units…" — I choose the victims. Without this section, reading my board into
// a one-sided wipe survived mutation: no other section has a wipe that spares its controller.
$board(6, [BOMB, 'ASH_053', OWEN, BARRISS], [$GROUND], [['Ground', 'SOR_095'], ['Ground', 'SOR_095'], ['Ground', 'SOR_095']]);
$new = $resourced(1, []);
$check($new[0] !== 'ASH_053', 'stabilized at 6R with the bigger board, a one-sided wipe (Pre Vizsla) is still kept; resourced ' . $new[0]);

SWUBotSetDisabledFeatures([]);
bot_test_finish();
