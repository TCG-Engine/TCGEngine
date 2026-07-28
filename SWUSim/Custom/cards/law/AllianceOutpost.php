<?php
// LAW_019
// Alliance Outpost - [Vigilance] - HP 26
// Text: Epic Action [defeat a friendly token]: Give an Experience or Shield token to a unit, or create a Credit token.

// LAW_019 Alliance Outpost — defeat the chosen token (cost), then choose the reward mode.
$customDQHandlers["LAW_019#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    SWUDefeatUnit(intval($player), $lastDecision);          // pay the [defeat a friendly token] cost
    DecisionQueueController::CleanupRemovedCards();
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&Experience&Shield&Credit", 1, "Give_an_Experience_or_Shield_token_to_a_unit,_or_create_a_Credit");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_019#1", 1);
};

$customDQHandlers["LAW_019#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'Credit') { SWUCreateCreditToken(intval($player), 1); SWUAfterAction(intval($player)); return; }
    $handler = ($lastDecision === 'Shield') ? 'GIVE_SHIELD' : 'GIVE_EXPERIENCE|1';
    $targets = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_the_token_to_a_unit", $handler);
    SWUQueueAfterAction(intval($player));
};
