<?php
// LAW_126
// Cost 2 - Adventurer Sniper Rifle - [Vigilance] - Upgrade Power 0 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / Attached unit gains: "Action [Exhaust]: Choose an undamaged non-leader ground unit. Its printed HP is considered to be 1 for this phase."

// LAW_126 Adventurer Sniper Rifle (granted) — "Action [Exhaust]: Choose an undamaged non-leader ground
// unit. Its printed HP is considered to be 1 for this phase." Provider = the upgrade CardID (the host
// is exhausted by the framework). Affordability requires ≥1 valid target (SWUUnitActionAffordable).
$unitAbilities["LAW_126"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (["myGroundArena", "theirGroundArena"] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) === 0) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Set_an_undamaged_non-leader_ground_unit's_printed_HP_to_1_this_phase", "LAW_126#0");
    SWUQueueAfterAction($player);
};

// Apply the SET_HP_1 phase marker to the chosen unit (read in ObjectCurrentHP; expires at regroup).
$customDQHandlers["LAW_126#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision))
    return;
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o))
    return;
  AddTurnEffect($lastDecision, 'SET_HP_1');
};
