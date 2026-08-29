<?php
// HMW_016
// Cost 7 - Maul - Old Master - [Cunning,Villainy] - Leader (deployed unit 5/6 Ground) - Traits: Force, Fringe - Unique
// Text:       Action [Exhaust]: Play a unit from your hand. It costs [1 resource] less. Then, defeat it.
//             (When Played abilities resolve after the unit is defeated.)
//             Epic Action: If you control 7 or more resources, deploy this leader.
// DeployText: Shielded
//             When Deployed: You may play a unit that was defeated this phase from your discard pile.
//             It costs [5 resources] less.
//
// SEC_018 DJ is the direct sibling — same Action shape, same -1, same parenthetical — differing only in
// "the chosen unit captures it" vs "defeat it". Its uniqueness-deferral machinery is reused here.
//
// ── WHY THE DEFEAT IS SYNCHRONOUS AND STILL LANDS FIRST ─────────────────────────────────────────────
// A leader ability resolves BEFORE the abilities it sets off (USER RULING). ActivateCard only QUEUES the
// played unit's When Played — FlushEntryTriggerBag hands it to the trigger orchestration rather than
// running it inline — so the synchronous SWUDefeatUnit below completes while that trigger is still
// pending, and the printed "(When Played abilities resolve after the unit is defeated)" falls out of the
// engine's normal ordering rather than needing a special case.
//
// A unit with BOTH a When Played and a When Defeated therefore ends up with TWO pending triggers after
// the defeat, and per the same ruling the controller may resolve them in either order — which is what
// the EffectStack ordering prompt exists for. TWI_208, LAW_091, LOF_207, ASH_167 and SOR_134 are the
// fixtures for that in the test file.
//
// Epic Action deploy needs no code at all: SWUDeployLeader gates on the leader's PRINTED COST, which is
// exactly the printed "7 or more resources". Shielded on the deployed side is likewise derived by the
// generator from deployTextData into $Shielded_Cards.
//
// ⚠ NO SWULeaderActionAffordable CASE, DELIBERATELY. The Action's only cost is the exhaust, so it is
// always legal to take as a SOFT PASS (the Thrawn ASH_004 ruling) — with an empty hand it resolves to
// nothing and Maul is still exhausted. Moving "do I have a playable unit?" into the affordability gate
// would make the action VANISH from the menu instead of resolving to nothing.

// ── FRONT: Action [Exhaust] ─────────────────────────────────────────────────────────────────────────
// SWULeaderAction has already exhausted Maul by the time this runs, so the closure only offers.
//
// ★ MZMAYCHOOSE even though the card prints no "may": playing a card FROM HAND is ALWAYS declinable
// (USER RULING) because the hand is a HIDDEN zone and a player can never be forced to reveal that they
// held a playable card. Declining does NOT refund the exhaust — the cost buys the ability, not the
// effect resolving.
//
// The candidate list comes from SWUPlayablesAtDiscount, which prices each card through the SAME
// pipeline that will charge the play (SWUComputePlayCost minus the discount, aspect penalty included)
// against SWUTotalPaymentCapacity — ready resources plus Credits and SEC_122 Droids. That also keeps
// this from offering a pick that could only fizzle.
//
// afterAction stays TRUE: this is a leader ACTION, so the empty-hand path must close it.
$leaderAbilities["HMW_016"] = function(int $player): void {
    $discount = 1;
    SWUOfferDiscountPlay($player, [
        'discount'     => $discount,
        'zone'         => 'myHand',
        'types'        => AnyUnitFilter,
        'may'          => true,
        'afterAction'  => true,
        'continuation' => 'HMW_016#0|' . $discount,   // ONE discount: the filter and the charge cannot disagree
        'question'     => "Play_a_unit_from_your_hand_for_1_less_and_defeat_it?",
        'prompt'       => "Play_a_unit_from_your_hand_(costs_1_less,_then_it_is_defeated)",
    ]);
};

// Step 0: play the chosen hand unit at the discount, then defeat it.
$customDQHandlers["HMW_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $handMz = strval($lastDecision);
    if (strpos($handMz, '-') === false) { SWUAfterAction(intval($player)); return; }

    // The marker is how the played unit is FOUND afterwards. A positional guess would be wrong as soon
    // as the unit lands in the other arena, or the arena reindexes.
    $gPlayGrantTurnEffect = 'HMW_016';
    // Nested play: the leader Action owns this action's ending. SWUNestedPlay neutralises BOTH of
    // ActivateCard's after-actions — the immediate one and the deferred one that a queued
    // SWU_TRIGGER_RESUME fires when the played unit arms an entry trigger (an opponent's Trap Field).
    SWUNestedPlay(intval($player), $handMz, false, intval($parts[0] ?? 0));
    $gPlayGrantTurnEffect = null;

    $newMz = null;
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null)
                    && in_array('HMW_016', $o->TurnEffects, true)) { $newMz = $mz; break 2; }
        }
    }

    // CR 1050.3 — playing a second copy of a unique card forces its controller to defeat one of them,
    // immediately, as a game RULE rather than a triggered ability, and ActivateCard can only QUEUE that
    // choice because it is interactive. Defer our own defeat behind it on the same queue (same player,
    // so plain append ordering holds) and re-resolve by UniqueID afterwards.
    //
    // ⚠ HONEST STATUS: on SEC_018 DJ this was a LIVE BUG, but DJ's second step is a CAPTURE — the
    // chosen copy becomes a subcard and is unfindable in any arena, so a stale offer cannot be
    // recovered. A DEFEAT compacts the arena predictably, and mutation testing here could not produce a
    // board where removing this branch changes the outcome: both uniqueness sections in the test file
    // stay green without it. It is kept as the sibling card's proven shape rather than because it is
    // demonstrably load-bearing on THIS card — do not copy it onward as if it were verified.
    $newUID = ($newMz !== null) ? intval((GetZoneObject($newMz)->UniqueID) ?? 0) : 0;
    if ($newUID > 0 && _SWUMaulUniquenessPending(intval($player))) {
        DecisionQueueController::AddDecision(intval($player), 'CUSTOM',
            "HMW_016#1|{$newUID}", 1, dontSkipOnPass: 1);
        return;   // HMW_016#1 owns the After Action
    }

    if ($newMz !== null) SWUDefeatUnit(intval($player), $newMz);
    SWUAfterAction(intval($player));
};

// True when ActivateCard queued the CR 1050.3 uniqueness choose-and-defeat for this player.
function _SWUMaulUniquenessPending(int $player): bool {
    foreach (GetDecisionQueue($player) as $entry) {
        if (strpos(strval($entry->Param ?? ''), 'UNIQUENESS_DEFEAT') === 0) return true;
    }
    return false;
}

// Step 1: the uniqueness defeat has resolved; defeat whatever survived of the copy just played.
// $newMz === null ⇒ the player already defeated the new copy, so there is nothing left to defeat.
$customDQHandlers["HMW_016#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $newMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($newMz !== null) SWUDefeatUnit(intval($player), $newMz);
    SWUAfterAction(intval($player));
};

// ── DEPLOYED: When Deployed ─────────────────────────────────────────────────────────────────────────
// "You may play a unit that was defeated this phase from your discard pile. It costs 5 less."
//
// "Defeated this phase" is the SWU_DEFEATED_CARD_<cardID> per-owner multiset, stamped at every defeat
// site and cleared at RegroupPhaseStart — the same filter TWI_189 Unnatural Life uses. It is a COUNTED
// multiset rather than a flag, so a card seeded into the discard by any other route is correctly
// excluded no matter how many copies are sitting there.
//
// afterAction is FALSE and the continuation adds none: this is a When Deployed, i.e. a whenPlayed
// trigger, and the deploy's own trigger flush owns the after-action.
$whenPlayedAbilities["HMW_016:0"] = function($player, $mzID = '') {
    $discount = 5;
    SWUOfferDiscountPlay(intval($player), [
        'discount'     => $discount,
        'zone'         => 'myDiscard',
        'types'        => AnyUnitFilter,
        'may'          => true,
        'afterAction'  => false,
        'filter'       => fn($cardID) => GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . $cardID) > 0,
        'continuation' => 'HMW_016#2|' . $discount,
        'question'     => "Play_a_unit_defeated_this_phase_from_your_discard_for_5_less?",
        'prompt'       => "Play_a_unit_defeated_this_phase_(costs_5_less)",
    ]);
};

// Step 2: play the chosen discard unit at the discount. No after-action — the deploy's flush owns it.
$customDQHandlers["HMW_016#2"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (strpos(strval($lastDecision), '-') === false) return;
    SWUNestedPlay(intval($player), strval($lastDecision), false, intval($parts[0] ?? 0));
};
