<?php
// SEC_074
// Cost 2 - Relief Request - [Vigilance]
// Text: Heal 3 damage from a unit. / You may disclose Vigilance (reveal a card from your hand with this aspect icon). If you do, heal 3 damage from another unit.

// SEC_074 Relief Request continuations — #0: heal the chosen unit 3, then offer the Vigilance
// disclose; #1: heal 3 from another (different) damaged unit.
$customDQHandlers["SEC_074#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    OnHealUnit(intval($player), $lastDecision, 3);
    SWUQueueDisclose(intval($player), ['Vigilance'], "SEC_074#1|{$firstUID}",
        "Disclose_Vigilance_to_heal_3_from_another_unit");
};

$customDQHandlers["SEC_074#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $firstUID = intval($parts[0] ?? 0);
    $damaged = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $firstUID) continue;   // "another unit"
        if (intval($o->Damage ?? 0) > 0) $damaged[] = $mz;
    }
    if (empty($damaged)) return;
    SWUQueueChooseTarget(intval($player), $damaged, "Heal_3_damage_from_another_unit", "HEAL_TARGET|3");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_074:0"] = function($player, $mzID = '') {
// Relief Request — "Heal 3 damage from a unit. You may disclose Vigilance →
                          // heal 3 damage from another unit." First heal (mandatory) over damaged units,
                          // then the optional disclose → a second heal on a DIFFERENT damaged unit.
            global $playerID; $playerID = intval($player);
            $damaged = [];
            foreach (array_merge(
                ZoneSearch("myGroundArena", AnyUnitFilter),    ZoneSearch("mySpaceArena", AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter), ZoneSearch("theirSpaceArena", AnyUnitFilter)
            ) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $damaged[] = $mz;
            }
            if (empty($damaged)) return;
            SWUQueueChooseTarget(intval($player), $damaged, "Heal_3_damage_from_a_unit", "SEC_074#0");
            return;
};
