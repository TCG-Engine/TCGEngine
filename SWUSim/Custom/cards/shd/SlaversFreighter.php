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
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach (['teamGroundArena', 'teamSpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID
                && intval($o->Status ?? 1) === 0 && ObjectCurrentPower($o) <= $count) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Ready_a_unit_(power<=enemy_upgrades)?", "Choose_a_unit_to_ready", "READY_UNIT");
};
