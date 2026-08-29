<?php
// LOF_016
// Cost 6 - Qui-Gon Jinn - Student of the Living Force - [Cunning,Heroism] - Power 4 - HP 7
// Text: Action [Exhaust, use the Force (lose your Force token)]: Return a friendly non-leader unit to its owner's hand. Play a non-Villainy unit that costs less than the returned unit from your hand for free.
// DeployText: When this unit completes an attack (and survives): You may return a friendly non-leader unit to its owner's hand. Play a non-Villainy unit that costs less than the returned unit from your hand for free.
// Epic Action: If you control 6 or more resources, deploy this leader.

// LOF_016 Qui-Gon Jinn — Action [Exhaust, use the Force]: Return a friendly non-leader unit to its owner's
// hand. Play a non-Villainy unit that costs less than the returned unit from your hand for free.
$leaderAbilities["LOF_016"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Return_a_friendly_non-leader_unit_to_hand", "LOF_016#0");
};

$customDQHandlers["LOF_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $returnedCost = intval(CardCost($o->CardID));
    SWUBounceUnit(intval($player), $lastDecision);
    $playables = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 999) as $mz) { // free play → ignore affordability
        $h = GetZoneObject($mz); if (SWUObjGone($h)) continue;
        if (intval(CardCost($h->CardID)) < $returnedCost && strpos(CardAspect($h->CardID) ?? '', 'Villainy') === false) $playables[] = $mz;
    }
    if (empty($playables)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $playables, "Play_a_cheaper_non-Villainy_unit_for_free", "LOF_016#1");
};

$customDQHandlers["LOF_016#1"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    SWUNestedPlay(intval($player), $lastDecision, true, 0); // ignoreCost = true (free)
    SWUAfterAction(intval($player));
};

// LOF_016 Qui-Gon Jinn (deployed) — When this unit completes an attack (and survives): you may return a
// friendly non-leader unit to its owner's hand, then play a non-Villainy unit costing less than the
// returned unit from your hand for free. Same effect as the front Action; combat owns the After Action,
// so the deployed continuations (#2/#3) never call SWUAfterAction.
$onAttackEndAbilities["LOF_016:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o) || IsLeaderUnit($o)) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_friendly_non-leader_unit?",
        "Return_a_friendly_non-leader_unit_to_hand", "LOF_016#2");
};

$customDQHandlers["LOF_016#2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $returnedCost = intval(CardCost($o->CardID));
    SWUBounceUnit(intval($player), $lastDecision);
    $playables = [];
    foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 999) as $mz) {
        $h = GetZoneObject($mz); if (SWUObjGone($h)) continue;
        if (intval(CardCost($h->CardID)) < $returnedCost && strpos(CardAspect($h->CardID) ?? '', 'Villainy') === false) $playables[] = $mz;
    }
    if (empty($playables)) return;
    SWUQueueChooseTarget(intval($player), $playables, "Play_a_cheaper_non-Villainy_unit_for_free", "LOF_016#3");
};

$customDQHandlers["LOF_016#3"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUNestedPlay(intval($player), $lastDecision, true, 0);   // ignoreCost = true (free)
};
