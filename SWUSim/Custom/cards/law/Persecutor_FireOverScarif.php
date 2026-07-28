<?php
// LAW_178
// Cost 9 - Persecutor - Fire Over Scarif - [Aggression,Villainy] - Power 9 - HP 7
// Text: When Played/On Attack: Choose an arena. You may deal 3 damage to each unit in that arena.

$customDQHandlers["LAW_178#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || $lastDecision === 'Pass') return;
    $zones = ($lastDecision === 'Space') ? ["mySpaceArena", "theirSpaceArena"] : ["myGroundArena", "theirGroundArena"];
    $uids = [];
    foreach ($zones as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID);
        }
    }
    foreach ($uids as $uid) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUDealDamageToUnit($mz, 3, intval($player));
    }
};

// LAW_178 Persecutor — When Played/On Attack: choose an arena. You may deal 3 damage to each unit in
// that arena.
$law178 = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  // "You MAY deal 3 damage to each unit in that arena" — offer a Pass so the optional effect can be declined.
  DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Ground&Space&Pass", 1, "Deal_3_to_each_unit_in_which_arena?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_178#0", 1);
};

$whenPlayedAbilities["LAW_178:0"] = $law178;

$onAttackAbilities["LAW_178:0"] = $law178;
