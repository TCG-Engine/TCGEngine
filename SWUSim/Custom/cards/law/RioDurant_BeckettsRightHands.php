<?php
// LAW_093
// Cost 4 - Rio Durant - Beckett's Right Hands - [Cunning,Vigilance] - Power 2 - HP 5
// Text: When Played: You may return a non-leader unit that costs 3 or less to its owner's hand. Then, its owner may play it for free. It gains Shielded for this phase.

// LAW_093 Rio Durant — When Played: you may return a non-leader unit that costs 3 or less to its
// owner's hand. Then its owner may play it for free; it gains Shielded for this phase. (LOF_185 pattern.)
$whenPlayedAbilities["LAW_093:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_(cost_3_or_less)?", "Choose_a_unit", "LAW_093#0");
};

$customDQHandlers["LAW_093#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $owner = intval($obj->Owner ?? $player);
    if ($owner <= 0) $owner = intval($player);
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    $hand = GetHand($owner);
    $idx  = count($hand) - 1;
    if ($idx < 0) return;
    $playerID = $owner;
    DecisionQueueController::AddDecision($owner, 'YESNO', '-', 1, tooltip: "Play_the_returned_unit_for_free_(gains_Shielded)?");
    DecisionQueueController::AddDecision($owner, 'CUSTOM', "LAW_093#1|myHand-{$idx}", 1);
};

$customDQHandlers["LAW_093#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect; $playerID = intval($player);
    $handMz = $parts[0] ?? '';
    $o = ($handMz !== '') ? GetZoneObject($handMz) : null;
    if (SWUObjGone($o)) return;
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantTurnEffect = 'SHIELDED';            // the replayed unit gains Shielded for this phase
    ActivateCard(intval($player), $handMz, true);
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
};
