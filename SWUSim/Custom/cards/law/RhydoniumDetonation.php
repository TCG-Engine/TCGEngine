<?php
// LAW_096
// Cost 7 - Rhydonium Detonation - [Cunning,Vigilance]
// Text: Each player may return a non-leader unit to its owner's hand. Then, defeat all non-leader units.

// LAW_096 Rhydonium Detonation — step 0: bounce the caster's chosen unit (if any), then offer the
// OPPONENT their optional bounce. parts: [opp, caster].
$customDQHandlers["LAW_096#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp    = intval($parts[0] ?? OtherPlayer(intval($player)));
    $caster = intval($parts[1] ?? intval($player));
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUBounceUnit($caster, $lastDecision);
    }
    // Offer the opponent their bounce (their-frame relative mzIDs).
    $playerID = $opp;
    $oppTargets = array_merge(
        ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
        ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
        ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
        ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
    );
    if (empty($oppTargets)) {
        RhydoniumDetonationDefeatAllNonLeader($caster);
        return;
    }
    SWUQueueMayChooseTarget($opp, $oppTargets, "Return_a_non-leader_unit_to_hand?",
        "Choose_a_non-leader_unit_to_return", "LAW_096#1|" . $caster);
};

// LAW_096 step 1: bounce the opponent's chosen unit (if any), then defeat all remaining non-leader units.
$customDQHandlers["LAW_096#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? intval($player));
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUBounceUnit(intval($player), $lastDecision);
    }
    RhydoniumDetonationDefeatAllNonLeader($caster);
};

// LAW_096 helper — defeat all non-leader units across all four arenas (UID snapshot, index-shift safe).
function RhydoniumDetonationDefeatAllNonLeader(int $caster): void
{
  global $playerID;
  $playerID = $caster;
  $uids = [];
  foreach (["myGroundArena", "mySpaceArena", "theirGroundArena", "theirSpaceArena"] as $zone) {
    foreach (ZoneSearch($zone, NonLeaderUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $uids[] = intval($o->UniqueID);
    }
  }
  foreach ($uids as $uid) {
    $playerID = $caster;
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null)
      SWUDefeatUnit($caster, $mz);
  }
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_096:0"] = function($player, $mzID = '') {
// Rhydonium Detonation — "Each player may return a non-leader unit to its
                          // owner's hand. Then, defeat all non-leader units." Caster's optional bounce,
                          // then opponent's, then a mass defeat of all remaining non-leader units.
            global $playerID; $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $targets = array_merge(
                ZoneSearch("myGroundArena",    NonLeaderUnitFilter),
                ZoneSearch("mySpaceArena",     NonLeaderUnitFilter),
                ZoneSearch("theirGroundArena", NonLeaderUnitFilter),
                ZoneSearch("theirSpaceArena",  NonLeaderUnitFilter)
            );
            if (empty($targets)) { return; }   // nothing to bounce or defeat
            SWUQueueMayChooseTarget(intval($player), $targets, "Return_a_non-leader_unit_to_hand?",
                "Choose_a_non-leader_unit_to_return", "LAW_096#0|" . $opp . "|" . intval($player));
            return;
};
