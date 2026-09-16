<?php
// HMW_218 New Tactics — Event, cost 5, [Cunning][Heroism], Learned/Tactic.
// "Choose a non-leader unit. Its owner puts it on the top or bottom of their deck. (It isn't defeated.)"
// LOF_200 Qui-Gon Jinn's shape, mandatory: the OWNER (not the controller) picks top or bottom.

$whenPlayedAbilities["HMW_218:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'HMW_218#0', 'nonLeader' => true,
        'prompt' => 'Choose_a_non-leader_unit_to_put_on_its_owners_deck',
    ]);
};

$customDQHandlers["HMW_218#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($o)) return;
    $owner = intval($o->Owner ?? 0); if ($owner <= 0) $owner = intval($o->Controller ?? $player);
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision($owner, "OPTIONCHOOSE", "Top&Bottom", 1,
        tooltip: "Put_the_unit_on_the_top_or_bottom_of_your_deck");
    DecisionQueueController::AddDecision($owner, "CUSTOM", "HMW_218#1|{$uid}", 1);
};

$customDQHandlers["HMW_218#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    SWUUnitToBottomOfDeck(intval($player), $mz, $lastDecision === 'Top');
};
