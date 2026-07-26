<?php
// SHD_227
// Cost 0 - Look the Other Way - [Cunning]
// Text: Exhaust a unit unless its controller pays 2 resources.

// ─── SHD_227 Look the Other Way (Event) ───────────────────────────────────────
// Exhaust a unit unless its controller pays 2 resources. Cross-player: the chosen unit's controller decides
// whether to pay (YESNO queued from a CUSTOM continuation under $playerID = controller).
$customDQHandlers["SHD_227#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $caster     = intval($parts[0] ?? $player);
    $controller = intval($o->Controller ?? $caster);
    $uid        = intval($o->UniqueID ?? 0);
    if (SWUResourceCount($controller, true) >= 2) {
        $playerID = $controller;
        DecisionQueueController::AddDecision($controller, 'YESNO', '-', 1, tooltip:"Pay_2_resources_to_prevent_the_exhaust?");
        DecisionQueueController::AddDecision($controller, 'CUSTOM', "SHD_227#1|{$caster}|{$uid}", 1);
    } else {
        OnExhaustCard($caster, $lastDecision);                  // can't pay → exhausted
    }
};

$customDQHandlers["SHD_227#1"] = function($controller, $parts, $lastDecision) {
    global $playerID; $playerID = intval($controller);
    $uid = intval($parts[1] ?? 0);
    if ($lastDecision === 'YES') {
        SWUExhaustResources(intval($controller), 2);            // pay 2 → prevents exhaust
    } else {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) OnExhaustCard(intval($controller), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_227:0"] = function($player, $mzID = '') {
// Look the Other Way — "Exhaust a unit unless its controller pays 2 resources."
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_(exhaust_unless_controller_pays_2)", "SHD_227#0|{$player}");
            return;
};
