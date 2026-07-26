<?php
// ASH_161
// Cost 7 - Zeb Orrelios - Fists Work Every Time - [Aggression,Heroism] - Power 5 - HP 7
// Text: When Played: Give 3 Advantage tokens to another unit. / When a friendly upgrade is defeated: Deal 1 damage to a base.

// ASH_161 Zeb Orrelios — When Played: give 3 Advantage tokens to ANOTHER unit. (The reactive "when a
// friendly upgrade is defeated: deal 1 to a base" half is handled by _SWUOnUpgradeDefeated.)
$whenPlayedAbilities["ASH_161:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Give_3_Advantage_tokens_to_another_unit", "GIVE_ADVANTAGE|3");
};
