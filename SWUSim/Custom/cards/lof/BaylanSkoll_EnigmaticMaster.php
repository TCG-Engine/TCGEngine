<?php
// LOF_185
// Cost 5 - Baylan Skoll - Enigmatic Master - [Cunning,Villainy] - Power 5 - HP 5
// Text: Hidden / When Played: You may use the Force. If you do, return a non-leader unit that costs 4 or less to its owner's hand. Then, its owner may play it for free.

// LOF_185 Baylan Skoll — Hidden + When Played: may use the Force → return a non-leader unit (printed
// cost ≤4, either player) to its OWNER's hand. Then its owner may play it for FREE. Cross-player when
// the bounced unit is the opponent's: the OWNER gets the optional free-play decision (LOF_015 pattern),
// and the replay is a full normal play (When-Played fires, Hidden/Ambush apply — JTL_089#1 free-play).
$whenPlayedAbilities["LOF_185:0"] = function($player, $mzID) {
    SWUQueueMayUseTheForce(intval($player), "Use_the_Force_to_return_a_non-leader_unit_(cost_4_or_less)?", "LOF_185#0");
};

$customDQHandlers["LOF_185#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    UseTheForce(intval($player));
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        if (intval(CardCost($o->CardID ?? '')) <= 4) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), array_values($targets), "Return_a_non-leader_unit_(cost_4_or_less)_to_its_owner's_hand", "LOF_185#1");
};

$customDQHandlers["LOF_185#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $owner = intval($obj->Owner ?? $player);
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    // The bounced card is appended to the OWNER's hand; offer the owner an optional free play of it.
    $hand = GetHand($owner);
    $idx  = count($hand) - 1;
    if ($idx < 0) return;
    $playerID = $owner;
    DecisionQueueController::AddDecision($owner, 'YESNO', '-', 1, tooltip: "Play_the_returned_unit_for_free?");
    DecisionQueueController::AddDecision($owner, 'CUSTOM', "LOF_185#2|myHand-{$idx}", 1);
};

$customDQHandlers["LOF_185#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $handMz = $parts[0] ?? '';
    $o = ($handMz !== '') ? GetZoneObject($handMz) : null;
    if (SWUObjGone($o)) return;
    // Mirror JTL_089#1: guard the nested play so it doesn't double-advance the outer action's turn/PASS.
    SWUNestedPlay(intval($player), $handMz, true, 0);   // free play from the owner's hand
};
