<?php
// SHD_191
// Cost 6 - Xanadu Blood - Cad Bane's Reward - [Cunning,Villainy] - Power 4 - HP 6
// Text: Raid 2 / When Played/On Attack: You may return another friendly non-leader Underworld unit to its owner's hand. If you do, exhaust an enemy unit or resource.

$customDQHandlers["SHD_191#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUBounceUnit(intval($player), $lastDecision)) return;
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    foreach (ZoneSearch('theirResources') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_an_enemy_unit_or_resource", "SHD_191#1");
};

$customDQHandlers["SHD_191#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) OnExhaustCard(intval($player), $lastDecision);
};

// ─── SHD_191 Xanadu Blood ─────────────────────────────────────────────────────
// Raid 2 (auto) + When Played / On Attack: You may return another friendly non-leader Underworld unit to
// its owner's hand. If you do, exhaust an enemy unit or resource.
$shd191XanaduBlood = function ($player, $mzID) {
  global $playerID;
  $playerID = intval($player);
  $self = GetZoneObject($mzID);
  $selfUID = SWUObjUID($self, 0);
  $targets = [];
  foreach (['myGroundArena', 'mySpaceArena'] as $z) {
    foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (
        $o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
        && TraitContains($o, 'Underworld')
      )
        $targets[] = $mz;
    }
  }
  SWUQueueMayChooseTarget(
    intval($player),
    $targets,
    "Return_a_friendly_Underworld_unit_to_hand?",
    "Choose_a_friendly_Underworld_unit",
    "SHD_191#0"
  );
};

$whenPlayedAbilities["SHD_191:0"] = $shd191XanaduBlood;

$onAttackAbilities["SHD_191:0"] = $shd191XanaduBlood;
