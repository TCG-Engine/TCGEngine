<?php
// LOF_200
// Cost 7 - Qui-Gon Jinn - The Negotiations Will Be Short - [Cunning,Heroism] - Power 7 - HP 5
// Text: Ambush / When Defeated: You may choose a non-leader ground unit. Its owner puts it on the top or bottom of their deck.

// LOF_200 Qui-Gon Jinn — Ambush + When Defeated: may choose a non-leader ground unit; its owner puts it
// on the top or bottom of their deck.
$whenDefeatedAbilities["LOF_200:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits(null, GroundArena) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && !IsLeaderUnit($o)) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Choose_a_non-leader_ground_unit_to_put_on_a_deck?", "Choose_a_unit", "LOF_200#0");
};

$customDQHandlers["LOF_200#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $owner = intval($o->Owner ?? 0); if ($owner <= 0) $owner = intval($o->Controller ?? $player);
    $uid   = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision($owner, "OPTIONCHOOSE", "Top&Bottom", 1,
        tooltip: "Put_the_unit_on_the_top_or_bottom_of_your_deck");
    DecisionQueueController::AddDecision($owner, "CUSTOM", "LOF_200#1|{$uid}", 1);
};

$customDQHandlers["LOF_200#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    SWUUnitToBottomOfDeck(intval($player), $mz, $lastDecision === 'Top');
};
