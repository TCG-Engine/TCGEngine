<?php
// SHD_048
// Cost 6 - Gentle Giant - [Heroism,Vigilance] - Power 2 - HP 8
// Text: Grit (This unit gets +1/+0 for each damage on it.) / On Attack: You may heal damage from another unit equal to the damage on this unit.

// ─── SHD_048 Gentle Giant ─────────────────────────────────────────────────────
// Grit (auto) + On Attack: You may heal damage from ANOTHER unit equal to the damage on this unit.
// Heal amount = Gentle Giant's current Damage (snapshotted at attack); skip the offer when it has none.
$onAttackAbilities["SHD_048:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $n = intval($self->Damage ?? 0);
    if ($n <= 0) return;                                            // heal 0 → no meaningful offer
    $selfUID = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
                && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Heal_{$n}_damage_from_another_unit?", "Heal_another_unit", "HEAL_TARGET|{$n}");
};
