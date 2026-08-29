<?php
// HMW_018
// Cost 5 - The Warrior - Deft Duelist - [Cunning,Heroism] - Power 3 - HP 6 - Tusken - unique
// Text: Action [1 resource, Exhaust]: Play a unit with 3 or less power from your hand (paying its cost)
//       and give it Ambush for this phase.
// Epic Action: If you control 5 or more resources, deploy this leader.
// DeployText: Ambush (When you play this leader, she may immediately attack an enemy unit.)
//             Raid 1 (This unit gets +1/+0 while attacking.)
//
// ── DEPLOYED SIDE: NO CODE ────────────────────────────────────────────────────────────────────────
// Both deployed abilities are plain keywords, auto-derived by the generator into $Ambush_Cards and
// $Raid_Cards['HMW_018'] = 1, and dispatched generically (CollectEntryTriggers reads
// HasKeyword_Ambush on the freshly deployed leader unit; combat reads GetKeyword_Raid_Value).
// The Epic deploy threshold is her printed cost (5), which is SWUDeployLeader's default branch.
// ⚠ She is the FIRST leader in the game whose deployed side has Ambush, so "the registries cover it"
// was a claim, not a fact — Tests/Cases/hmw/TheWarrior_DeftDuelist.md proves it through the real
// deploy→entry-trigger path, and doing so surfaced the missing Ambush-attack exhaust that is fixed in
// GameLogic.php's SWUAmbushAnswer/SWUAmbushAttack (a deployed leader enters play READY, so unlike every
// previously-Ambushing card she could observe it).

// ── FRONT SIDE ────────────────────────────────────────────────────────────────────────────────────
// The continuation that actually plays the chosen unit. Mirrors SHD_016 Fennec Shand, whose front
// Action is the same shape with a COST gate instead of a POWER gate.
$customDQHandlers["HMW_018#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    // Declined ('-') or a stale slot: the activation cost is still spent (see the offer note below).
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }

    // "and give it Ambush for this phase" — the entry-grant seam. ActivateCard stamps this token on the
    // entering unit BEFORE CollectEntryTriggers runs, which is what lets the granted Ambush fire its
    // attack on the way in. The "^HMW_018" provenance suffix makes the Active Effects popup show THIS
    // card's art as the source (SWUParseTurnEffect strips it); the synthetic 'AMBUSH' base carries the
    // keyword and the registry's default phase duration. Same form as LOF_180 and LAW_015.
    $gPlayGrantTurnEffect = SWUMakeTurnEffect('AMBUSH', [], SWU_DUR_PHASE, 'HMW_018');

    // Nested play: neutralise the inner ActivateCard's own turn advance (JTL_089#1 save/restore), so the
    // leader action advances the turn exactly once, below.
    SWUNestedPlay(intval($player), $mz, false, 0);   // "(paying its cost)" — no discount
    $gPlayGrantTurnEffect = null;

    SWUAfterAction(intval($player));
};

$leaderAbilities["HMW_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!TheWarriorDeftDuelistOffer($player)) SWUAfterAction($player);
};

// "Action [1 resource, Exhaust]" — the exhaust is applied by SWULeaderAction and the resource is paid
// centrally through SWUOfferAltPayment, so Credit tokens / SEC_122 Droids can pay it (CR 3.13).
$leaderActionResourceCosts["HMW_018"] = 1;

// Collect the legal hand units. Returns false when there are none, so the caller can close the action:
// the ability still cost [1 resource, Exhaust] and simply does nothing, which is the house reading of
// CR 6.4.0.e/f (an action ability may be used with no valid subject as long as paying its cost changes
// the game state) — SWUSim raises no "use it anyway?" confirmation.
function TheWarriorDeftDuelistOffer(int $player): bool {
    global $playerID; $playerID = $player;
    // Total payment capacity, never a bare ready-resource count: a player holding 1 ready resource and a
    // Credit token CAN pay a cost of 2, and gating on SWUResourceCount would silently drop the card from
    // the offer entirely.
    $capacity = SWUTotalPaymentCapacity($player);
    $units = [];
    foreach (ZoneSearch('myHand') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (stripos(CardType($o->CardID) ?? '', 'Unit') === false) continue;
        // "with 3 or less power" — the PRINTED power of the card in hand. For a Piloting card that is
        // its UNIT power (powerData), not its upgrade power: this ability plays it as a unit.
        if (intval(CardPower($o->CardID ?? '')) > 3) continue;
        // An offer the player cannot pay for could only fizzle, so it is not offered at all.
        if (SWUComputePlayCost($player, $o) > $capacity) continue;
        $units[] = $mz;
    }
    if (empty($units)) return false;
    // DECLINABLE (user ruling 2026-08-15): "play a unit from your HAND" can always be declined, because
    // the hand is a HIDDEN zone — a player is never forced to reveal that they held a playable unit.
    // The card prints no "you may"; it is declinable anyway. Same reasoning as SHD_016 Fennec Shand and
    // SHD_129 Timely Intervention. Declining does NOT refund the [1 resource, Exhaust].
    SWUQueueMayChooseTarget($player, $units,
        "Play_a_unit_with_3_or_less_power_(it_gains_Ambush)?",
        "Play_a_unit_with_3_or_less_power_(it_gains_Ambush)", "HMW_018#0");
    return true;
}
