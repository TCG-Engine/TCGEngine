<?php
// LAW_257
// Cost 1 - Hidden Hand Supplier - Power 1 - HP 2
// Text: When Played: You may pay 1 resource. If you do, give an Experience token to another unit.

// LAW_257 Hidden Hand Supplier — When Played: you may pay 1 resource. If you do, give an Experience
// token to another unit.
$whenPlayedAbilities["LAW_257:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), readyOnly: true) < 1) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_an_Experience_token_to_another_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_257#0|{$uid}", 1);
};

$customDQHandlers["LAW_257#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 1)) return;
    $uid = intval($parts[0] ?? 0);
    $others = [];
    foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) $others[] = $mz;
        }
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Give_an_Experience_token_to_another_unit", "GIVE_EXPERIENCE|1");
};
