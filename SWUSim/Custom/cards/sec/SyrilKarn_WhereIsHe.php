<?php
// SEC_133
// Cost 2 - Syril Karn - Where Is He? - [Aggression,Villainy] - Power 2 - HP 3
// Text: On Attack: You may disclose AggressionAggressionVillainy (reveal cards from your hand with these aspect icons among them). If you do, choose a unit. Deal 2 damage to that unit unless its controller discards a card from their hand.

// SEC_133 Syril Karn — On Attack: you may disclose AggressionAggressionVillainy → choose a unit; deal 2
// to it unless its controller discards a card from their hand. (#1 picks the unit, #2 = the controller's
// pay-or-suffer decision — a cross-player decision queued onto the target's controller.)
$onAttackAbilities["SEC_133:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Aggression', 'Aggression', 'Villainy'], "SEC_133#0",
        "Disclose_AggressionAggressionVillainy_to_pressure_a_unit");
};

$customDQHandlers["SEC_133#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $units = SWUAllUnits();
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit", "SEC_133#1|" . intval($player));
};

$customDQHandlers["SEC_133#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $attacker = intval($parts[0] ?? $player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $tgtUID = intval($o->UniqueID ?? 0);
    $ctrl   = intval($o->Controller ?? GetOpponent($attacker));
    $ctrlHand = GetHand($ctrl);
    if (empty($ctrlHand)) {                       // can't discard → deal 2 directly
        SWUDealDamageToUnit($lastDecision, 2, $attacker);
        return;
    }
    // The controller decides: discard a card to prevent the damage, or take 2.
    $playerID = $ctrl;
    DecisionQueueController::AddDecision($ctrl, "YESNO", "-", 1,
        tooltip: "Discard_a_card_to_prevent_2_damage_to_your_unit?");
    DecisionQueueController::AddDecision($ctrl, "CUSTOM", "SEC_133#2|{$tgtUID}|{$attacker}", 1);
};

$customDQHandlers["SEC_133#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the target's controller
    $tgtUID   = intval($parts[0] ?? 0);
    $attacker = intval($parts[1] ?? 0);
    if ($lastDecision === 'YES') {
        $hand = ZoneSearch("myHand", null);
        if (!empty($hand)) {
            SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand",
                "DISCARD_FROM_OWN_HAND|" . intval($player));
            return;   // discarded → damage prevented
        }
    }
    // declined (or somehow no card) → deal 2 to the target. Resolve the mzID in the ATTACKER's frame
    // (SWUFindMzByUID is $playerID-relative, and SWUDealDamageToUnit re-resolves under its $player arg).
    $playerID = intval($attacker);
    $tgtMz = SWUFindMzByUID($tgtUID);
    if ($tgtMz !== null) SWUDealDamageToUnit($tgtMz, 2, $attacker);
};
