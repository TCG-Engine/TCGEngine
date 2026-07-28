<?php
// SOR_167
// Cost 1 - Force Throw - [Aggression]
// Text: Choose a player. That player discards a card from their hand. Then, if you control a FORCE unit, you may deal damage to a unit equal to the cost of the discarded card.

$customDQHandlers["SOR_167#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $target = SWUDecodePlayerPick($lastDecision, $caster); // "You"→caster, "Opponent"/"P{n}"→that player
    // Compact zones first: Force Throw itself is a just-played event still sitting as a removed entry
    // in the caster's hand, which would desync ZoneSearch (skips removed) from GetZoneObject (raw idx).
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $target;
    $hand = array_values(ZoneSearch("myHand"));   // all card types, discarding player's perspective
    if (empty($hand)) { $playerID = $caster; return; }   // no card to discard → nothing happens
    if (count($hand) === 1) {
        // Deterministic — discard it directly (avoids a fragile cross-player auto-resolve).
        _SWUForceThrowDiscard($target, $caster, $hand[0]);
    } else {
        // The DISCARDING player chooses which card; the follow-up runs the caster's optional damage.
        SWUQueueChooseTarget($target, $hand, "Discard_a_card_from_your_hand", "SOR_167#1|" . $caster);
    }
};

$customDQHandlers["SOR_167#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    _SWUForceThrowDiscard(intval($player), intval($parts[0] ?? $player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_167:0"] = function($player, $mzID = '') {
// Force Throw — "Choose a player. That player discards a card from their hand.
                          // Then, if you control a FORCE unit, you may deal damage to a unit equal to the
                          // cost of the discarded card."
            DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", SWUPlayerPickerLabels(intval($player)), 1, "Which_player_discards_a_card?");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_167#0", 1);
            return;
};
