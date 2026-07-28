<?php
// JTL_213
// Cost 2 - Sidon Ithano - The Crimson Corsair - [Cunning] - Power 2 - HP 2 - Upgrade Power -2 - Upgrade HP -2
// Text: When played as a unit: You may attach this unit as an upgrade to an enemy Vehicle unit without a Pilot on it.

// JTL_213 Sidon Ithano — When played as a unit: You may attach this unit as an upgrade to an enemy
// Vehicle unit without a Pilot on it. (Becomes a pilot on the enemy ship — it buffs the enemy host;
// that is what the card does.)
$whenPlayedAbilities["JTL_213:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $uid = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (['theirGroundArena','theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (!HasTrait($o->CardID ?? '', 'Vehicle')) continue;
            if (_SWUFindPilotSubcard($o) !== null) continue; // already has a pilot
            $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Attach_Sidon_to_an_enemy_Vehicle", "Choose_an_enemy_Vehicle", "JTL_213#0|" . $uid);
};

$customDQHandlers["JTL_213#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $selfMz = SWUFindMzByUID($uid);
    if ($selfMz === null) return;
    SWUMoveUnitToUpgrade($selfMz, $lastDecision, true); // attach Sidon as a Pilot onto the enemy Vehicle
};
