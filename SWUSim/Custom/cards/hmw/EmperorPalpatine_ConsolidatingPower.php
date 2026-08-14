<?php
// HMW_110
// Cost 5 - Emperor Palpatine, Consolidating Power - [Command][Villainy] - Unit (Ground) 3/2 - unique
// Traits: Force, Imperial, Sith, Official
// Text: When Played: You may take control of an enemy non-leader unit that costs 3 or less.
//       If you do, give 2 Weakness tokens to it.
//
// TARGET POOL — three restrictions, each independently guarded in the test file:
//   • ENEMY      → side 'their'
//   • NON-LEADER → nonLeader, which routes through IsLeaderUnit on the LIVE object. This is the
//                  load-bearing form: a unit made a leader unit by ASH_135 The Darksaber (or by a
//                  leader Pilot upgrade) still has printed CardType "Unit" and can cost 3 or less, so
//                  a printed-type check would wrongly offer it. Same shape as the ASH_093 Pellaeon bug.
//   • costs 3 or less → extraFilter on the PRINTED cost (CardCost), never an effective/discounted one.
//
// The control change is PERMANENT — the text names no duration — so this uses SWUTakeControlOfUnit
// directly and must NOT stamp the TEMPORARY_STEAL marker that RegroupPhaseStart auto-reverts
// (LOF_189 / SOR_224). A regroup-crossing section pins that.
$whenPlayedAbilities["HMW_110:0"] = function ($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), (string)$mzID, [
        'continuation' => 'HMW_110#0',
        'may'          => true,
        'side'         => 'their',                       // "an ENEMY … unit"
        'nonLeader'    => true,                          // "NON-LEADER unit" — live IsLeaderUnit read
        'extraFilter'  => fn($o) => intval(CardCost($o->CardID ?? '')) <= 3,
        'prompt'       => 'Take_control_of_an_enemy_non-leader_unit_that_costs_3_or_less',
    ]);
};

// "If you do" — gate the tokens on the take-control actually SUCCEEDING rather than assuming it did.
// SWUTakeControlOfUnit returns '' when it refuses (LAW_149 Rey's "opponents can't take control of this
// unit", or the CR 3.4.6 leader-unit rule that defeats instead of transferring). Neither is reachable
// from this card's <=3 cost pool today — Rey costs 8 and leader units are filtered out of the offer —
// so the branch is implemented but has no fixture; see the note in the test file.
//
// ⚠ The returned mzID is in the NEW CONTROLLER's frame (here $player's, since $player is taking
// control), and it is NOT the mzID that was chosen — the unit physically moves from the opponent's
// arena into ours, so the answer's `theirGroundArena-N` is stale the moment control transfers. Always
// use the return value.
$customDQHandlers["HMW_110#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);

    $newMz = SWUTakeControlOfUnit(intval($player), (string)$lastDecision);
    if ($newMz === '') return;                       // refused → "If you do" fails, no tokens

    $playerID = intval($player);
    DoGiveTokenUpgrade(intval($player), $newMz, 'HMW_T02');
    DoGiveTokenUpgrade(intval($player), $newMz, 'HMW_T02');
    SWUCheckShrinkDefeats();                         // -2 HP can drop the host to 0 remaining
};
