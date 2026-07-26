<?php
// IBH_064
// Cost 4 - Hoth Lieutenant - [Aggression,Villainy] - Power 3 - HP 4
// Text: When Played: You may attack with another unit. It gets +2/+0 for this attack.  (You can only attack with a ready friendly unit.)

// IBH_064 / IBH_092 Hoth Lieutenant — When Played: you may attack with another unit; it gets +2/+0 for
// this attack. Reuses the IBH_021#0 continuation (+2/+0 then BeginSWUAttack). "Another" ready unit only.
$whenPlayedAbilities["IBH_064:0"] =
$whenPlayedAbilities["IBH_092:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (intval($u->UniqueID ?? -1) === $selfUID) continue; // "another" unit
            $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Attack_with_another_unit_(+2/+0)?",
        "Choose_a_unit_to_attack_with", "IBH_021#0");
};
