<?php
// HMW_172
// Cost 3 - Heavy Ion Cannon - [Aggression][Heroism] - Upgrade - Trait: Fortification - NON-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       When Played: Draw a card.
//       Attached base gains: "Action [discard a card from your hand]: Deal 2 damage to a unit.
//       Use this ability only once each phase."
//
// FORTIFY needs no code — HMW_172 is in $Fortify_Cards (generator-derived) and SWUGetUpgradeValidTargets
// routes a Fortify upgrade to ['myBase-0']. Same as HMW_037 Bacta Tank and HMW_112 Military Academy.
//
// THE GRANTED ACTION is base-hosted: $baseUpgradeAbilities, keyed by the UPGRADE's CardID and reached by
// clicking the base. The closure owns its After Action, exactly like a $baseAbilities closure.
//
// ── "USE THIS ABILITY ONLY ONCE EACH PHASE" — why it is a COUNT and not a per-subcard flag ──────────
// The card is NON-UNIQUE, so a base can carry two Heavy Ion Cannons and each grants its own Action with
// its own once-each-phase allowance: two copies must permit two activations in a phase.
// A per-instance flag is not available. Base subcards carry no UniqueID (the attach helpers build them
// with CardID/Owner/Controller/TurnEffects/IsPilot only), and their TurnEffects array is NOT swept —
// SWUExpireTurnEffects walks the ground and space arenas, never a base or its subcards, so a marker
// parked there would never expire (the SWU_DUR_ROUND never-expired bug family). Subcard INDEX is not
// stable either: defeating one upgrade shifts the rest.
// So the allowance is counted per phase against the number of attached copies. The one behaviour this
// does not model is attach-A, use-A, defeat-A, attach-B within a single phase, where B is denied a use
// it arguably owns; that requires a per-instance identity the data model does not carry.
// The counter is cleared in RegroupPhaseStart beside its siblings.

// The bracketed COST is "discard a card from your hand", so an empty hand makes the Action unpayable and
// it must not be offered at all — the LAW_023 / LAW_019 rule, and the fizzle-only-optional family.
// ⚠ Reads GetHand/$base directly rather than ZoneSearch: this gate also runs from the UI glow path
// (SWUComputeActionsData), where the global $playerID is not guaranteed to be this player, and a
// frame-relative "myHand" would silently answer for the wrong seat.
$baseUpgradeActionAvailable["HMW_172"] = function(int $player, int $upgradeIndex, $base): bool {
    $inHand = 0;
    foreach (GetHand($player) as $c) { if (empty($c->removed)) $inHand++; }
    if ($inHand === 0) return false;

    $copies = 0;
    foreach (GetUpgradesOnUnit($base) as $sub) {
        $cid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
        if ($cid === 'HMW_172') $copies++;
    }
    return GlobalEffectCount($player, 'SWU_HMW172_USED') < $copies;
};

// ── When Played: Draw a card ──────────────────────────────────────────────────────────────────────
// Unconditional, and it fires through the CollectWhenPlayedAsUpgradeTriggers fallback (a non-pilot
// upgrade with only a WhenPlayed stub), which hands the closure the HOST's mzID — here the base. The
// host is irrelevant to a draw, so $mzID goes unused.
$whenPlayedAbilities["HMW_172:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    DoDrawCard(intval($player), 1);
};

// ── Action [discard a card from your hand]: Deal 2 damage to a unit ───────────────────────────────
$baseUpgradeAbilities["HMW_172"] = function(int $player, int $upgradeIndex): void {
    global $playerID;
    $playerID = $player;

    // Spend the use on ACTIVATION, not on the effect landing: the ability has been used even if the
    // damage finds no target, and an Action that fizzles still pays its cost (house ruling — there is
    // no "use it anyway?" confirmation).
    AddGlobalEffects($player, 'SWU_HMW172_USED');

    // Mandatory: this is a COST of an Action the player chose to activate, not an optional effect, so
    // there is no decline. The availability gate above guarantees the hand is non-empty.
    $hand = ZoneSearch("myHand");
    if (empty($hand)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $hand, "Discard_a_card_from_your_hand", "HMW_172#0");
};

// ── Step 0: the cost is paid, then the damage target is chosen ────────────────────────────────────
$customDQHandlers["HMW_172#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);

    // DoDiscardCard is the self-chosen path: it stamps From:HAND and fires OnCardDiscarded, which the
    // "when this card is discarded" observers gate on. Routing to the discard by hand would skip both.
    if (!SWUDecisionDeclined($lastDecision)) DoDiscardCard(intval($player), (string)$lastDecision);

    // "Deal 2 damage to A UNIT" — unqualified: no controller, no arena, no non-leader clause. Every unit
    // on the table is legal, including the player's own and deployed leader units. (Contrast HMW_046
    // Krrsantan, whose text narrows this to "a ground unit".) SWUAllUnits() with no arguments is that
    // pool, and it spans team + every opponent at any seat count.
    $targets = SWUAllUnits();
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }   // cost paid, effect fizzles
    SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_unit", "HMW_172#1");
};

// ── Step 1: deal the 2 ────────────────────────────────────────────────────────────────────────────
$customDQHandlers["HMW_172#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (!SWUDecisionDeclined($lastDecision)) {
        SWUDealDamageToUnit((string)$lastDecision, 2, intval($player));
    }
    SWUAfterAction(intval($player));
};
