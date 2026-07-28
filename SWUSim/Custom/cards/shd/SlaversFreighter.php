<?php
// SHD_189
// Cost 5 - Slaver's Freighter - [Cunning,Villainy] - Power 4 - HP 5
// Text: When Played: You may ready another unit with power equal to or less than the number of upgrades on enemy units.

// ─── SHD_189 Slaver's Freighter ───────────────────────────────────────────────
// When Played: You may ready another unit with power equal to or less than the number of upgrades on
// enemy units.
$whenPlayedAbilities["SHD_189:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $count = 0;
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            foreach (GetUpgradesOnUnit($o) as $s) {
                if (strpos(CardType($s->CardID ?? '') ?? '', 'Upgrade') !== false) $count++;
            }
        }
    }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
                && intval($o->Status ?? 1) === 0 && ObjectCurrentPower($o) <= $count) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Ready_a_unit_(power<=enemy_upgrades)?", "Choose_a_unit_to_ready", "READY_UNIT");
};
