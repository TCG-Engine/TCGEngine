<?php
// LOF_036
// Cost 5 - Old Daka - Oldest and Wisest - [Vigilance,Villainy] - Power 6 - HP 6
// Text: When Played: You may defeat a friendly Night unit not named Old Daka. Then, you may play that unit from your discard pile for free.

// LOF_036 Old Daka — When Played: may defeat a friendly Night unit (not Old Daka). Then, may play that
// unit from your discard pile for free.
$whenPlayedAbilities["LOF_036:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || ($o->CardID ?? '') === 'LOF_036') continue;
        if (HasTrait($o->CardID ?? '', 'Night')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Defeat_a_friendly_Night_unit?", "Choose_a_Night_unit", "LOF_036#0");
};

$customDQHandlers["LOF_036#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    SWUDefeatUnit(intval($player), $lastDecision); // → discard
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Play_that_unit_from_your_discard_for_free?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_036#1|{$cardID}", 1);
};

$customDQHandlers["LOF_036#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $cardID = $parts[0] ?? '';
    $discard = GetDiscard(intval($player));
    $liveIdx = -1; $count = 0;
    for ($i = 0; $i < count($discard); $i++) {
        if (!empty($discard[$i]->removed)) continue;
        if (($discard[$i]->CardID ?? '') === $cardID) $liveIdx = $count; // last matching live entry
        $count++;
    }
    if ($liveIdx < 0) return;
    // Nested play: Old Daka's own When Played flush owns this action's ending — ActivateCard must not
    // finalise it again, and the deferred leg matters too (the replayed unit can arm an entry trigger).
    SWUNestedPlay(intval($player), "myDiscard-{$liveIdx}", false, 99); // free (via canonical play)
};
