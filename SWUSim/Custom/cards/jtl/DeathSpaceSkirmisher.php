<?php
// JTL_217
// Cost 3 - Death Space Skirmisher - [Cunning] - Power 3 - HP 3
// Text: When Played: If you control another space unit, you may exhaust a unit.

// ── JTL_217 Death Space Skirmisher — When Played: If you control another space unit, you may exhaust a
// unit. ──────────────────────────────────────────────────────────────────────────────────────────────
$whenPlayedAbilities["JTL_217:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $another = false;
    foreach (ZoneSearch("mySpaceArena", AnyUnitFilter) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid) { $another = true; break; }
    }
    if (!$another) return;
    $targets = SWUAllUnits();
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "You_may_exhaust_a_unit", "Exhaust_a_unit", "EXHAUST_UNIT");
};
